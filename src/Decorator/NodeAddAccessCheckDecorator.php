<?php

namespace Drupal\config_entity_access_deny\Decorator;

use Drupal\config_entity_access_deny\ConfigEntityAccessDenyTrait;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\Access\NodeAddAccessCheck;
use Drupal\node\NodeTypeInterface;

/**
 * Class NodeAddAccessCheckDecorator.
 *
 * Decorates the access method for NodeAddAccessCheck.
 *
 * @package Drupal\config_entity_access_deny
 */
class NodeAddAccessCheckDecorator extends NodeAddAccessCheck {

  use ConfigEntityAccessDenyTrait;

  /**
   * The subject.
   *
   * @var \Drupal\node\Access\NodeAddAccessCheck
   */
  protected $subject;

  /**
   * Constructs a NodeAddAccessCheckDecorator object.
   *
   * @param \Drupal\node\Access\NodeAddAccessCheck $subject
   *   The subject.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(NodeAddAccessCheck $subject, EntityTypeManagerInterface $entity_type_manager) {
    $this->subject = $subject;
    parent::__construct($entity_type_manager);
  }

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account, NodeTypeInterface $node_type = NULL) {
    $access = $this->subject->access($account, $node_type);
    if ($node_type && $this->isDeniedEntity($node_type)) {
      $access = AccessResult::forbidden();
    }
    return $access;
  }

}
