<?php

namespace Drupal\config_entity_access_deny\Routing;

use Symfony\Component\Routing\RouteCollection;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;

/**
 * Content Entity Access Deny route subscriber.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[RoutingEvents::ALTER] = ['onAlterRoutes', -200];
    return $events;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Add '_config_entity_access_deny' access checker.
    foreach ($collection->all() as $route) {
      $route->setRequirement('_config_entity_access_deny', 'TRUE');
    }
    // Alter 'block_content.add_page' _controller.
    if ($route = $collection->get('block_content.add_page')) {
      $route->setDefault('_controller', '\Drupal\config_entity_access_deny\Controller\BlockContentController::add');
    }
    // Alter 'user.admin_permissions' _form.
    if ($route = $collection->get('user.admin_permissions')) {
      $route->setDefault('_form', '\Drupal\config_entity_access_deny\Form\UserPermissionsForm');
    }
  }

}
