<?php

namespace Drupal\config_entity_access_deny\Decorator;

use Drupal\config_entity_access_deny\ConfigEntityAccessDenyTrait;
use Drupal\config_entity_access_deny\Form\ConfigEntityAccessDenyConfigForm;
use Drupal\Core\Access\AccessManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Menu\MenuLinkTreeInterface;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

/**
 * Class ToolbarMenuTreeDecorator.
 *
 * Decorates the build method for ToolbarMenuTree.
 *
 * @package Drupal\config_entity_access_deny
 */
class ToolbarMenuTreeDecorator implements MenuLinkTreeInterface {

  use ConfigEntityAccessDenyTrait;

  /**
   * The subject.
   *
   * @var \Drupal\Core\Menu\MenuLinkTreeInterface
   */
  protected $subject;

  /**
   * The config entities access deny settings.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * The access manager.
   *
   * @var \Drupal\Core\Access\AccessManagerInterface
   */
  protected $accessManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The route provider.
   *
   * @var \Drupal\Core\Routing\RouteProviderInterface
   */
  protected $routeProvider;

  /**
   * Constructs a \Drupal\Core\Menu\MenuLinkTree object.
   *
   * @param \Drupal\Core\Menu\MenuLinkTreeInterface $subject
   *   The subject.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Access\AccessManagerInterface $access_manager
   *   The access manager.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Routing\RouteProviderInterface $route_provider
   *   The route provider.
   */
  public function __construct(
    MenuLinkTreeInterface $subject,
    ConfigFactoryInterface $config_factory,
    AccessManagerInterface $access_manager,
    AccountInterface $current_user,
    ModuleHandlerInterface $module_handler,
    RouteProviderInterface $route_provider
  ) {
    $this->subject = $subject;
    $this->config = $config_factory->get(ConfigEntityAccessDenyConfigForm::CONFIG_NAME);
    $this->accessManager = $access_manager;
    $this->currentUser = $current_user;
    $this->moduleHandler = $module_handler;
    $this->routeProvider = $route_provider;
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrentRouteMenuTreeParameters($menu_name) {
    return $this->subject->getCurrentRouteMenuTreeParameters($menu_name);
  }

  /**
   * {@inheritdoc}
   */
  public function load($menu_name, MenuTreeParameters $parameters) {
    return $this->subject->load($menu_name, $parameters);
  }

  /**
   * {@inheritdoc}
   */
  public function transform(array $tree, array $manipulators) {
    return $this->subject->transform($tree, $manipulators);
  }

  /**
   * {@inheritdoc}
   */
  public function build(array $tree, $level = 0) {
    $build = $this->subject->build($tree, $level);

    // @see \Drupal\toolbar\Menu\ToolbarMenuLinkTree::build(), 17.
    if (isset($build['#items'])) {
      // Filter menu tree items.
      // @see admin_toolbar_links_access_filter.module
      // @see admin_toolbar_links_access_filter_filter_non_accessible_links().
      $this->menuTreeTraversalsFilter($build['#items'], function (&$item, $key) {
        $route_name = $key;
        $route_params = [];

        // @see admin_toolbar_links_access_filter_filter_non_accessible_links():65
        if (!empty($item['original_link']) && $link = $item['original_link']) {
          /** @var \Drupal\Core\Menu\MenuLinkDefault $link */

          // Do not filter external URL at all.
          if ($link->getUrlObject()->isExternal()) {
            return TRUE;
          }

          $route_name = $link->getRouteName();
          $route_params = $link->getRouteParameters();
        }

        $route_match = $this->generateRouteMatch($route_name, $route_params);

        // Checks, if the route match was denied.
        if ($route_match && $this->isDeniedRouteMatch($route_match)) {
          return FALSE;
        }

        // Checks, the current user no admin.
        if (!$this->hasAdminRole($this->currentUser)) {
          // Checks, if the user not has access rights to the route.
          // @see admin_toolbar_links_access_filter.module:77
          if (!$this->accessManager->checkNamedRoute($route_name, $route_params)) {
            return FALSE;
          }

          // Checks, if the menu item not has subtree,
          // admin_toolbar module exists,
          // and the current user not have admin role.
          // @see admin_toolbar_links_access_filter.module:85
          if (empty($item['below']) && $this->moduleHandler->moduleExists('admin_toolbar')) {
            // Every child item has been cleared out.
            // Now check, if the given route represents an overview page only,
            // without having functionality on its own.
            // @see admin_toolbar_links_access_filter.module:93
            if ($this->isOverviewPage($route_name)) {
              return FALSE;
            }
            else {
              // Let's remove the expanded flag.
              $item['is_expanded'] = FALSE;
            }
          }
        }

        // Default is true.
        return TRUE;
      });

      // Cache info.
      $build['#cache']['contexts'][] = 'url.site';
      $build['#cache']['tags'][] = 'config:' . ConfigEntityAccessDenyConfigForm::CONFIG_NAME;
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function maxDepth() {
    return $this->subject->maxDepth();
  }

  /**
   * {@inheritdoc}
   */
  public function getSubtreeHeight($id) {
    return $this->subject->getSubtreeHeight($id);
  }

  /**
   * {@inheritdoc}
   */
  public function getExpanded($menu_name, array $parents) {
    return $this->subject->getExpanded($menu_name, $parents);
  }

  /**
   * Menu tree traversals filter.
   *
   * @param array $items
   *   The items.
   * @param callable $callback
   *   Callback function.
   */
  protected function menuTreeTraversalsFilter(array &$items, callable $callback) {
    foreach ($items as $key => $item) {
      // Do something.
      // If the item no hide.
      if ($callback($items[$key], $key)) {
        if (isset($items[$key]['below'])) {
          $this->menuTreeTraversalsFilter($items[$key]['below'], $callback);
        }
      }
      // If the item need hide.
      else {
        unset($items[$key]);
      }
    }
  }

  /**
   * Checks if the given route name is an overview page.
   *
   * Checks if the given route name matches a pure (admin) overview page
   * that can be skipped, if there are no child items set.
   * The typical example are routes having
   * the SystemController::systemAdminMenuBlockPage() function as their
   * controller callback set.
   *
   * @param string $route_name
   *   The route name to check.
   *
   * @return bool
   *   TRUE, if the given route name matches a pure admin overview page route,
   *   FALSE otherwise.
   *
   * @see admin_toolbar_links_access_filter.module
   * @see admin_toolbar_links_access_filter_is_overview_page()
   */
  protected function isOverviewPage(string $route_name) {
    $overview_page_controllers = [
      '\Drupal\system\Controller\AdminController::index',
      '\Drupal\system\Controller\SystemController::overview',
      '\Drupal\system\Controller\SystemController::systemAdminMenuBlockPage',
    ];
    try {
      $route = $this->routeProvider->getRouteByName($route_name);
      $controller = $route->getDefault('_controller');
      return !empty($controller) && in_array($controller, $overview_page_controllers);
    }
    catch (RouteNotFoundException $error) {
      return FALSE;
    }
    return FALSE;
  }

}
