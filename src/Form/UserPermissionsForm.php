<?php

namespace Drupal\config_entity_access_deny\Form;

use Drupal\config_entity_access_deny\ConfigEntityAccessDenyTrait;
use Drupal\user\Form\UserPermissionsForm as DrupalUserPermissionsForm;

/**
 * Class UserPermissionsForm.
 */
class UserPermissionsForm extends DrupalUserPermissionsForm {

  use ConfigEntityAccessDenyTrait;

  /**
   * {@inheritdoc}
   */
  protected function getRoles() {
    $roles = parent::getRoles();
    if ($settings = $this->getSettingsByEntityType('user_role')) {
      foreach ($roles as $id => $role) {
        if (in_array($id, $settings)) {
          unset($roles[$id]);
        }
      }
    }
    return $roles;
  }

}
