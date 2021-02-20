<?php

namespace Drupal\permission_access_deny\Decorator;

use Drupal\permission_access_deny\PermissionAccessDenyHelper;
use Drupal\user\PermissionHandlerInterface;

/**
 * Class PermissionHandlerDecorator.
 */
class PermissionHandlerDecorator implements PermissionHandlerInterface {

  /**
   * The permission handler.
   *
   * @var \Drupal\user\PermissionHandlerInterface
   */
  protected $subject;

  /**
   * The denied permissions.
   *
   * @var array
   */
  protected $deniedPermissions = [];

  /**
   * Constructs PermissionHandlerDecorator.
   *
   * @param \Drupal\user\PermissionHandlerInterface $subject
   *   The subject.
   */
  public function __construct(PermissionHandlerInterface $subject) {
    $this->subject = $subject;
    $this->deniedPermissions = array_flip(PermissionAccessDenyHelper::getDeniedPermissions());
  }

  /**
   * {@inheritdoc}
   */
  public function getPermissions() {
    $permissions = $this->subject->getPermissions();
    return array_diff_key($permissions, $this->deniedPermissions);
  }

  /**
   * {@inheritdoc}
   */
  public function moduleProvidesPermissions($module_name) {
    return $this->subject->moduleProvidesPermissions($module_name);
  }

}
