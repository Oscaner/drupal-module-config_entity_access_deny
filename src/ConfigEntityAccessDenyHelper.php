<?php

namespace Drupal\config_entity_access_deny;

use Drupal\config_entity_access_deny\Form\ConfigEntityAccessDenyConfigForm;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Class ConfigEntityAccessDenyHelper.
 */
class ConfigEntityAccessDenyHelper {

  /**
   * Get settings by entity type id.
   *
   * @param string|null $entity_type_id
   *   The entity type id.
   *
   * @return array
   *   The related settings.
   */
  public static function getSettingsByEntityType(string $entity_type_id) {
    $access_deny_settings = &drupal_static(ConfigEntityAccessDenyConfigForm::STATIC_NAME, []);

    // Checks, affect config types.
    $affect_config_type_key = ConfigEntityAccessDenyConfigForm::AFFECT_CONFIG_TYPE_KEY;

    if (!isset($access_deny_settings[$affect_config_type_key])) {
      $access_deny_settings[$affect_config_type_key] = \Drupal::configFactory()
        ->get(ConfigEntityAccessDenyConfigForm::CONFIG_NAME)
        ->get($affect_config_type_key) ?: [];
    }

    // Checks, if the entity type not in affect config type list.
    if (!in_array($entity_type_id, $access_deny_settings[$affect_config_type_key])) {
      return [];
    }

    // Checks, affect config entities.
    $affect_config_entity_key = ConfigEntityAccessDenyConfigForm::AFFECT_CONFIG_ENTITY_KEY;

    if (!isset($access_deny_settings[$affect_config_entity_key])) {
      $access_deny_settings[$affect_config_entity_key] = \Drupal::configFactory()
        ->get(ConfigEntityAccessDenyConfigForm::CONFIG_NAME)
        ->get($affect_config_entity_key) ?: [];
    }

    if (!isset($access_deny_settings[$affect_config_entity_key][$entity_type_id])) {
      $access_deny_settings[$affect_config_entity_key][$entity_type_id] = [];
    }

    // Return affect config entities of config type.
    return $access_deny_settings[$affect_config_entity_key][$entity_type_id];
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
  public static function isDenied(string $entity_type_id = NULL, string $entity_id = NULL) {
    // Not has entity_type_id, or not has entity_id.
    if (!$entity_type_id || !$entity_id) {
      return FALSE;
    }

    $settings = self::getSettingsByEntityType($entity_type_id);

    return in_array($entity_id, $settings);
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
  public static function isDeniedEntityType(string $entity_bundle = NULL, EntityTypeInterface $entity_type = NULL) {
    // Not has entity type.
    if (!$entity_bundle || !$entity_type) {
      return FALSE;
    }

    if ($entity_type instanceof ContentEntityTypeInterface) {
      $bundle_entity_type = $entity_type->getBundleEntityType();
      return self::isDenied($bundle_entity_type, $entity_bundle);
    }

    return FALSE;
  }

}
