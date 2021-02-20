<?php

namespace Drupal\permission_access_deny\Form;

use Drupal\config_entity_access_deny\Form\ConfigEntityAccessDenyConfigForm;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\permission_access_deny\PermissionAccessDenyHelper;
use Drupal\user\PermissionHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class PermissionAccessDenyConfigForm.
 */
class PermissionAccessDenyConfigForm extends ConfigFormBase {

  use StringTranslationTrait;

  /**
   * Drupal static variable name.
   */
  const STATIC_NAME = ConfigEntityAccessDenyConfigForm::STATIC_NAME;

  /**
   * The config file name.
   */
  const CONFIG_NAME = ConfigEntityAccessDenyConfigForm::CONFIG_NAME;

  /**
   * The affect permission.
   */
  const AFFECT_PERMISSION_KEY = 'affect_permission';

  /**
   * The permission handler.
   *
   * @var \Drupal\user\PermissionHandlerInterface
   */
  protected $permissionHandler;

  /**
   * All permissions.
   *
   * @var array
   */
  protected $allPermissions = [];

  /**
   * Construct PermissionAccessDenyConfigForm.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\user\PermissionHandlerInterface $permission_handler
   *   The permission handler.
   */
  public function __construct(ConfigFactoryInterface $config_factory, PermissionHandlerInterface $permission_handler) {
    parent::__construct($config_factory);
    $this->permissionHandler = $permission_handler;
    $this->allPermissions = array_map(function ($permission) {
      return $permission['title'];
    }, $permission_handler->getPermissions());
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('permission_access_deny.permission_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [self::CONFIG_NAME];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'permission_access_deny_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form[self::AFFECT_PERMISSION_KEY] = [
      '#type' => 'details',
      '#title' => $this->t('Affect permissions'),
      '#description' => $this->t('Choose the permissions, there will be hidden in site.'),
      '#weight' => 0,
      '#element_validate' => [[get_class($this), 'validateElement']],
    ];
    $form[self::AFFECT_PERMISSION_KEY][self::AFFECT_PERMISSION_KEY] = [
      '#type' => 'checkboxes',
      '#options' => $this->allPermissions,
      '#default_value' => PermissionAccessDenyHelper::getDeniedPermissions(),
      '#validated' => TRUE,
      '#multiple' => TRUE,
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * Form validation handler for Numbers Shown element.
   *
   * @param array $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function validateElement(array $element, FormStateInterface $form_state) {
    // Affect config types value.
    if (isset($element[self::AFFECT_PERMISSION_KEY]['#value'])) {
      $form_state->setValueForElement($element, $element[self::AFFECT_PERMISSION_KEY]['#value']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config(self::CONFIG_NAME);
    $input_value__affect_permissions = $form_state->getValue(self::AFFECT_PERMISSION_KEY, []);
    $input_value__affect_permissions = array_values(array_filter($input_value__affect_permissions));
    $config->set(self::AFFECT_PERMISSION_KEY, $input_value__affect_permissions)->save();
    // Static cache cleared.
    drupal_static_reset(self::STATIC_NAME);

    parent::submitForm($form, $form_state);
  }

}
