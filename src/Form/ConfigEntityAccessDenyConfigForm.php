<?php

namespace Drupal\config_entity_access_deny\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\Entity\ConfigEntityTypeInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ConfigEntityAccessDenyConfigForm.
 */
class ConfigEntityAccessDenyConfigForm extends ConfigFormBase {

  /**
   * Drupal static variable name.
   */
  const STATIC_NAME = 'config_entity_access_deny_settings';

  /**
   * The config file name.
   */
  const CONFIG_NAME = 'config_entity_access_deny.settings';

  /**
   * The affect config type key.
   */
  const AFFECT_CONFIG_TYPE_KEY = 'affect_config_type';

  /**
   * The affect config entity key.
   */
  const AFFECT_CONFIG_ENTITY_KEY = 'affect_config_entity';

  /**
   * The container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  protected $container;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Construct ConfigEntityAccessDenyConfigForm.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(ContainerInterface $container, ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($config_factory);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container,
      $container->get('config.factory'),
      $container->get('entity_type.manager')
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
    return 'config_entity_access_deny_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Affect config types.
    $entity_types = $this->getConfigEntityTypes(FALSE);
    $form[self::AFFECT_CONFIG_TYPE_KEY] = [
      '#type' => 'details',
      '#title' => $this->t('Affect config types'),
      '#description' => $this->t('Choose the config types, there will be allowed to be set access denied.'),
      '#weight' => 0,
      '#element_validate' => [[get_class($this), 'validateElement']],
    ];
    $options = [];
    foreach ($entity_types as $entity_type) {
      $options[$entity_type->id()] = $entity_type->getLabel() ?: $entity_type->id();
    }
    $form[self::AFFECT_CONFIG_TYPE_KEY][self::AFFECT_CONFIG_TYPE_KEY] = [
      '#type' => 'checkboxes',
      '#options' => $options,
      '#default_value' => $this->config(self::CONFIG_NAME)->get(self::AFFECT_CONFIG_TYPE_KEY) ?: [],
      '#validated' => TRUE,
      '#multiple' => TRUE,
    ];

    // Affect config entities.
    $form[self::AFFECT_CONFIG_ENTITY_KEY] = [
      '#type' => 'details',
      '#title' => $this->t('Affect config entities'),
      '#description' => $this->t('Choose the config entities, there will be allowed to be set access denied.'),
      '#weight' => 1,
      '#element_validate' => [[get_class($this), 'validateElement']],
    ];
    foreach ($entity_types as $weight => $entity_type) {
      $entity_type_id = $entity_type->id();

      // Set form.
      $form[self::AFFECT_CONFIG_ENTITY_KEY][$entity_type_id] = [
        '#type' => 'details',
        '#title' => $entity_type->getLabel(),
        '#weight' => $weight,
        '#states' => [
          'invisible' => [
            ':input[name="' . self::AFFECT_CONFIG_TYPE_KEY . '[' . $entity_type_id . ']"]' => ['checked' => FALSE],
          ],
        ],
      ];

      // Load entities by entity type.
      $entities = array_map(function (EntityInterface $entity) {
        return $entity->label() ?: $entity->id();
      }, $this->entityTypeManager->getStorage($entity_type_id)->loadMultiple());
      // Sort entities.
      asort($entities);

      // If entities empty, unset.
      if (empty($entities)) {
        unset($form[$entity_type_id]);
      }
      // If entities no empty, set entity checkbox.
      else {
        $form[self::AFFECT_CONFIG_ENTITY_KEY][$entity_type_id][$entity_type_id] = [
          '#type' => 'checkboxes',
          '#options' => $entities,
          '#default_value' => $this->config(self::CONFIG_NAME)->get(self::AFFECT_CONFIG_ENTITY_KEY . '.' . $entity_type_id) ?: [],
          '#validated' => TRUE,
          '#multiple' => TRUE,
        ];
      }

      // Free.
      unset($entities);
    }

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
    if (isset($element[self::AFFECT_CONFIG_TYPE_KEY]['#value'])) {
      $form_state->setValueForElement($element, $element[self::AFFECT_CONFIG_TYPE_KEY]['#value']);
      return;
    }
    // Affect config entities value.
    $values = [];
    foreach ($element as $entity_type_id => $form_array) {
      if (is_array($form_array) && isset($form_array[$entity_type_id]['#value'])) {
        $values[$entity_type_id] = $form_array[$entity_type_id]['#value'];
      }
    }
    $form_state->setValueForElement($element, $values);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config(self::CONFIG_NAME);
    $config_value__affect_config_entities = $config->get(self::AFFECT_CONFIG_ENTITY_KEY) ?: [];
    $input_value__affect_config_entities = $form_state->getValue(self::AFFECT_CONFIG_ENTITY_KEY, []);

    // Affect config types.
    $input_value__affect_config_types = $form_state->getValue(self::AFFECT_CONFIG_TYPE_KEY, []);
    $input_value__affect_config_types = array_values(array_filter($input_value__affect_config_types));
    $config->set(self::AFFECT_CONFIG_TYPE_KEY, $input_value__affect_config_types)->save();

    // Affect config entities.
    foreach ($input_value__affect_config_entities as $entity_type_id => $value) {
      $old_values = $config_value__affect_config_entities[$entity_type_id] ?? [];
      $new_values = array_values(array_filter($value));
      if ($old_values != $new_values) {
        $config_value__affect_config_entities[$entity_type_id] = $new_values;
      }
    }
    $config->set(self::AFFECT_CONFIG_ENTITY_KEY, $config_value__affect_config_entities)->save();

    // Static cache cleared.
    drupal_static_reset(self::STATIC_NAME);

    parent::submitForm($form, $form_state);
  }

  /**
   * Get config entity types.
   *
   * @param bool $only_affect
   *   TRUE, the result only affect config entities,
   *   false otherwise.
   *
   * @return \Drupal\Core\Config\Entity\ConfigEntityTypeInterface[]
   *   The array of config entity types.
   */
  protected function getConfigEntityTypes($only_affect = TRUE) {
    $entity_types = [];
    // Only affect.
    if ($only_affect) {
      $affect_config_types = $this->config(self::CONFIG_NAME)->get(self::AFFECT_CONFIG_TYPE_KEY) ?: [];
      $entity_types = array_filter($this->entityTypeManager->getDefinitions(), function ($entity_type) use ($affect_config_types) {
        return $entity_type instanceof ConfigEntityTypeInterface && in_array($entity_type->id(), $affect_config_types);
      });
    }
    // Full entity types.
    else {
      $entity_types = array_filter($this->entityTypeManager->getDefinitions(), function ($entity_type) {
        return $entity_type instanceof ConfigEntityTypeInterface;
      });
    }
    // Sort entity types.
    usort($entity_types, function (ConfigEntityTypeInterface $a, ConfigEntityTypeInterface $b) {
      return strcasecmp($a->getLabel(), $b->getLabel());
    });
    return $entity_types;
  }

}
