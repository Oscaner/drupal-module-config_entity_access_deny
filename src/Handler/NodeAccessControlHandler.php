<?php

namespace Drupal\config_entity_access_deny\Handler;

use Drupal\config_entity_access_deny\ConfigEntityAccessDenyTrait;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeAccessControlHandler as DrupalNodeAccessControlHandler;

/**
 * Class NodeAccessControlHandler.
 */
class NodeAccessControlHandler extends DrupalNodeAccessControlHandler {

  use ConfigEntityAccessDenyTrait;

  /**
   * {@inheritdoc}
   */
  public function createAccess($entity_bundle = NULL, AccountInterface $account = NULL, array $context = [], $return_as_object = FALSE) {
    $result = parent::createAccess($entity_bundle, $account, $context, TRUE);
    if ($this->isDeniedEntityType($entity_bundle, $this->entityType)) {
      $result = AccessResult::forbidden();
    }
    return $return_as_object ? $result : $result->isAllowed();
  }

}
