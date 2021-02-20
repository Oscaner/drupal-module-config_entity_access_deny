<?php

namespace Drupal\permission_access_deny;

use Drupal\permission_access_deny\Form\PermissionAccessDenyConfigForm;

/**
 * Class PermissionAccessDenyHelper.
 */
class PermissionAccessDenyHelper {

  /**
   * Get denied permissions.
   *
   * @return array
   *   The denied permissions.
   */
  public static function getDeniedPermissions() {
    $access_deny_settings = &drupal_static(PermissionAccessDenyConfigForm::STATIC_NAME, []);

    $affect_permission_key = PermissionAccessDenyConfigForm::AFFECT_PERMISSION_KEY;

    if (!isset($access_deny_settings[$affect_permission_key])) {
      $access_deny_settings[$affect_permission_key] = \Drupal::configFactory()
        ->get(PermissionAccessDenyConfigForm::CONFIG_NAME)
        ->get($affect_permission_key) ?: [];
    }

    return $access_deny_settings[$affect_permission_key];
  }

}
