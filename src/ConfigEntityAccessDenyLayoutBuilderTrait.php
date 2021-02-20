<?php

namespace Drupal\config_entity_access_deny;

use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Trait ConfigEntityAccessDenyLayoutBuilderTrait.
 */
trait ConfigEntityAccessDenyLayoutBuilderTrait {

  /**
   * Checks, if the layout builder exists.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   *
   * @return bool
   *   TRUE, if the layout builder exists.
   */
  protected function isLayoutBuilderRouteMatch(RouteMatchInterface $route_match) {
    // Layout builder no exists.
    if (!\Drupal::moduleHandler()->moduleExists('layout_builder')) {
      return FALSE;
    }
    // Layout section storage.
    if ($route_match->getParameter('section_storage')) {
      return TRUE;
    }
    // False for default.
    return FALSE;
  }

  /**
   * Checks, if the given layout builder route match was denied.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   *
   * @return bool
   *   TRUE, if the given layout builder route match was denied,
   *   false otherwise.
   */
  protected function isDeniedLayoutBuilderRouteMatch(RouteMatchInterface $route_match) {
    // No layout builder route match.
    if (!$this->isLayoutBuilderRouteMatch($route_match)) {
      return FALSE;
    }

    $plugin_id = $route_match->getParameter('plugin_id') ?: NULL;

    // Not have plugin id.
    if (empty($plugin_id)) {
      return FALSE;
    }

    if (substr($plugin_id, 0, strlen('inline_block:')) === 'inline_block:') {
      [, $entity_bundle] = explode(':', $plugin_id);
      if (ConfigEntityAccessDenyHelper::isDenied('block_content_type', $entity_bundle)) {
        return TRUE;
      }
    }

    return FALSE;
  }

}
