<?php

namespace Drupal\config_entity_access_deny;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\ParamConverter\ParamNotConvertedException;
use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\Entity\Role;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

/**
 * Trait ConfigEntityAccessDenyTrait.
 */
trait ConfigEntityAccessDenyTrait {

  /**
   * Get config entity from route match.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   *
   * @return \Drupal\Core\Config\Entity\ConfigEntityInterface|null
   *   The config entity, or null.
   */
  protected function getConfigEntityByRouteMatch(RouteMatchInterface $route_match) {
    $entity = NULL;
    foreach ($route_match->getParameters()->all() as $parameter) {
      if ($parameter instanceof ConfigEntityInterface) {
        $entity = $parameter;
        break;
      }
    }
    return $entity;
  }

  /**
   * Generate route match by route name and route parameters.
   *
   * @param string $route_name
   *   The route name.
   * @param array $parameters
   *   The route parameters.
   *
   * @return \Drupal\Core\Routing\RouteMatchInterface|null
   *   The route match, or null.
   */
  protected function generateRouteMatch(string $route_name, array $parameters = []) {
    try {
      $route = \Drupal::service('router.route_provider')->getRouteByName($route_name, $parameters);

      // ParamConverterManager relies on the route name and object being
      // available from the parameters array.
      $parameters[RouteObjectInterface::ROUTE_NAME] = $route_name;
      $parameters[RouteObjectInterface::ROUTE_OBJECT] = $route;
      $upcasted_parameters = \Drupal::service('paramconverter_manager')->convert($parameters + $route->getDefaults());

      return new RouteMatch($route_name, $route, $upcasted_parameters, $parameters);
    }
    catch (RouteNotFoundException $e) {
      return NULL;
    }
    catch (ParamNotConvertedException $e) {
      return NULL;
    }
  }

  /**
   * Get settings by entity type id.
   *
   * @param string|null $entity_type_id
   *   The entity type id.
   *
   * @return array
   *   The related settings.
   */
  protected function getSettingsByEntityType(string $entity_type_id) {
    return ConfigEntityAccessDenyHelper::getSettingsByEntityType($entity_type_id);
  }

  /**
   * Checks, if the given was denied.
   *
   * @param string|null $entity_type_id
   *   The entity type id.
   * @param string|null $entity_id
   *   The entity id.
   *
   * @return bool
   *   TRUE, if the given was denied,
   *   false otherwise.
   */
  protected function isDenied(string $entity_type_id = NULL, string $entity_id = NULL) {
    return ConfigEntityAccessDenyHelper::isDenied($entity_type_id, $entity_id);
  }

  /**
   * Checks, if the given was denied.
   *
   * @param string|null $entity_bundle
   *   The entity type id.
   * @param \Drupal\Core\Entity\EntityTypeInterface|null $entity_type
   *   The entity id.
   *
   * @return bool
   *   TRUE, if the given was denied,
   *   false otherwise.
   */
  protected function isDeniedEntityType(string $entity_bundle = NULL, EntityTypeInterface $entity_type = NULL) {
    return ConfigEntityAccessDenyHelper::isDeniedEntityType($entity_bundle, $entity_type);
  }

  /**
   * Checks if the given entity was denied.
   *
   * @param \Drupal\Core\Entity\EntityInterface|null $entity
   *   The entity.
   *
   * @return bool
   *   TRUE, if the given route match was denied,
   *   false otherwise.
   */
  protected function isDeniedEntity(EntityInterface $entity = NULL) {
    // No config entity.
    if (!($entity instanceof ConfigEntityInterface)) {
      return FALSE;
    }
    return $this->isDenied($entity->getEntityTypeId(), $entity->id());
  }

  /**
   * Checks if the given route match was denied.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   *
   * @return bool
   *   TRUE, if the given route match was denied,
   *   false otherwise.
   */
  protected function isDeniedRouteMatch(RouteMatchInterface $route_match) {
    $entity = $this->getConfigEntityByRouteMatch($route_match);
    return $this->isDeniedEntity($entity);
  }

  /**
   * Checks, if the given user has admin rights.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account to check.
   *
   * @return bool
   *   TRUE, if the given user account has at least one role with admin rights
   *   assigned, FALSE otherwise.
   */
  protected function hasAdminRole(AccountInterface $account) {
    static $user_has_admin_role = [];

    $uid = $account->id();

    if (isset($user_has_admin_role[$uid])) {
      return $user_has_admin_role[$uid];
    }

    $roles = Role::loadMultiple($account->getRoles());
    foreach ($roles as $role) {
      $user_has_admin_role[$uid] = $role->isAdmin();
      if ($role->isAdmin()) {
        break;
      }
    }

    return $user_has_admin_role[$uid] ?? FALSE;
  }

}
