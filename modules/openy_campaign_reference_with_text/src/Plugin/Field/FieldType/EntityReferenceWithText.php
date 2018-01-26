<?php

/**
 * @file
 * Contains \Drupal\openy_campaign_reference_with_text\Plugin\Field\FieldType\EntityReferenceWithText.
 */

namespace Drupal\openy_campaign_reference_with_text\Plugin\Field\FieldType;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\DataReferenceTargetDefinition;

/**
 * Plugin implementation of the 'entity_reference_with_text' field type.
 *
 * @FieldType(
 *   id = "entity_reference_with_text",
 *   label = @Translation("Entity Reference With additional textfield for each entity"),
 *   description = @Translation("Stores an entity reference and a value for each Entity."),
 *   default_widget = "entity_reference_with_text_widget",
 *   default_formatter = "entity_reference_with_text_formatter",
 *   list_class = "\Drupal\Core\Field\EntityReferenceFieldItemList"
 * )
 */
class EntityReferenceWithText extends EntityReferenceItem implements FieldItemInterface  {
  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return [
      'max_length' => 255,
      'is_ascii' => FALSE,
      'case_sensitive' => FALSE,
    ] + parent::defaultStorageSettings();
  }
  
  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    // Add our properties.
    $properties['target_id'] = DataReferenceTargetDefinition::create('integer')
      ->setLabel(t('Branch Id'))
      ->setDescription(t('Selected Branch'));

    $properties['value'] = DataDefinition::create('string')
      ->setLabel(t('Target'))
      ->setDescription(t('Target number'));

    return $properties;

  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $target_type = $field_definition->getSetting('target_type');
    $target_type_info = \Drupal::entityManager()->getDefinition($target_type);

    $columns = [
      'target_id' => [
        'description' => 'The ID of the target entity.',
        //'type' => 'varchar_ascii',
        'type' => 'int',
        // If the target entities act as bundles for another entity type,
        // their IDs should not exceed the maximum length for bundles.
        'length' => $target_type_info->getBundleOf() ? EntityTypeInterface::BUNDLE_MAX_LENGTH : 255,
      ],
    ];


    $columns['value'] = [
      'type' => 'varchar',
      'length' => (int) 10,
      'binary' => TRUE,
    ];

    $schema = [
      'columns' => $columns,
      'indexes' => [
        'target_id' => ['target_id'],
      ],
    ];

    return $schema;
  }
  
  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $elements = [];

    $elements['target_type'] = [
      '#type' => 'select',
      '#title' => t('Type of item to reference'),
      '#options' => \Drupal::entityManager()->getEntityTypeLabels(TRUE),
      '#default_value' => $this->getSetting('target_type'),
      '#required' => TRUE,
      '#disabled' => $has_data,
      '#size' => 1,
    ];
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    if (!parent::isEmpty()) {
      return FALSE;
    }
    $value = $this->get('value')->getValue();
    $target_id = $this->get('target_id')->getValue();

    return empty($target_id) || empty($value);
  }
  

  /**
   * {@inheritdoc}
   */
  public static function getPreconfiguredOptions() {
    return [];
  }
}