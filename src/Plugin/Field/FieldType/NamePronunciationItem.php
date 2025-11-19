<?php

namespace Drupal\name_pronunciation\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Provides a field type for name pronunciation audio files.
 *
 * @FieldType(
 *   id = "name_pronunciation",
 *   label = @Translation("Name Pronunciation"),
 *   description = @Translation("Stores an audio recording of a name pronunciation."),
 *   default_widget = "name_pronunciation_recorder",
 *   default_formatter = "name_pronunciation_player",
 *   category = @Translation("Reference"),
 * )
 */
class NamePronunciationItem extends EntityReferenceItem {

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return [
      'target_type' => 'file',
      'uri_scheme' => 'public',
    ] + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return [
      'file_extensions' => 'webm ogg mp3 mp4 m4a wav',
      'max_duration' => 10,
      'handler' => 'default:file',
      'handler_settings' => [],
    ] + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = parent::propertyDefinitions($field_definition);

    $properties['description'] = DataDefinition::create('string')
      ->setLabel(t('Description'))
      ->setDescription(t('A description of the pronunciation (e.g., "First name", "Last name", "Full name")'));

    $properties['written_pronunciation'] = DataDefinition::create('string')
      ->setLabel(t('Written Pronunciation'))
      ->setDescription(t('A text representation of how to pronounce the name (e.g., "CARE-sun", "car-SON")'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = parent::schema($field_definition);

    $schema['columns']['description'] = [
      'description' => 'A description of the pronunciation.',
      'type' => 'varchar',
      'length' => 255,
    ];

    $schema['columns']['written_pronunciation'] = [
      'description' => 'A written/phonetic representation of the pronunciation.',
      'type' => 'varchar',
      'length' => 255,
    ];

    return $schema;
  }

}
