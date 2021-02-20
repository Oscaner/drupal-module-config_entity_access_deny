<?php

namespace Drupal\config_entity_access_deny\Access;

use Drupal\config_entity_access_deny\ConfigEntityAccessDenyLayoutBuilderTrait;
use Drupal\config_entity_access_deny\ConfigEntityAccessDenyTrait;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;

/**
 * Class ConfigEntityAccessDenyCheck.
 */
class ConfigEntityAccessDenyCheck implements AccessInterface {

  use ConfigEntityAccessDenyTrait;
  use ConfigEntityAccessDenyLayoutBuilderTrait;

  /**
   * Checks access for the route using the _config_entity_access_deny checker.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match object to be checked.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account being checked.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(Route $route, RouteMatchInterface $route_match, AccountInterface $account) {
    // Layout builder route.
    // @see https://www.drupal.org/project/drupal/issues/3130215
    if ($this->isDeniedLayoutBuilderRouteMatch($route_match)) {
      return AccessResult::forbidden();
    }
    // The route match was denied.
    if ($this->isDeniedRouteMatch($route_match)) {
      return AccessResult::forbidden();
    }
    return AccessResult::allowed();
  }

}
