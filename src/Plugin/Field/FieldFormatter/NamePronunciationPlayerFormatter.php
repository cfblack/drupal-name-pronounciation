<?php

namespace Drupal\name_pronunciation\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a formatter for playing name pronunciations.
 *
 * @FieldFormatter(
 *   id = "name_pronunciation_player",
 *   label = @Translation("Audio Player"),
 *   field_types = {
 *     "name_pronunciation"
 *   }
 * )
 */
class NamePronunciationPlayerFormatter extends FormatterBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'show_description' => TRUE,
      'show_written_pronunciation' => TRUE,
      'button_text' => 'Listen to pronunciation',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    $elements['show_description'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show description'),
      '#default_value' => $this->getSetting('show_description'),
    ];

    $elements['show_written_pronunciation'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show written pronunciation'),
      '#default_value' => $this->getSetting('show_written_pronunciation'),
    ];

    $elements['button_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Button text'),
      '#default_value' => $this->getSetting('button_text'),
      '#description' => $this->t('Text to display on the play button.'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    if ($this->getSetting('show_description')) {
      $summary[] = $this->t('Show description: Yes');
    }
    else {
      $summary[] = $this->t('Show description: No');
    }

    if ($this->getSetting('show_written_pronunciation')) {
      $summary[] = $this->t('Show written pronunciation: Yes');
    }
    else {
      $summary[] = $this->t('Show written pronunciation: No');
    }

    $summary[] = $this->t('Button text: @text', ['@text' => $this->getSetting('button_text')]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $file = NULL;

      // Prioritize uploaded file over recorded file.
      if (!empty($item->upload_target_id)) {
        /** @var \Drupal\file\FileInterface $file */
        $file = $this->entityTypeManager->getStorage('file')->load($item->upload_target_id);
      }
      // Fall back to recorded file if no upload exists.
      elseif (!empty($item->target_id)) {
        /** @var \Drupal\file\FileInterface $file */
        $file = $item->entity;
      }

      if ($file) {
        $elements[$delta] = [
          '#theme' => 'name_pronunciation_audio_player',
          '#audio_url' => $file->createFileUrl(),
          '#file_mime_type' => $file->getMimeType(),
          '#button_text' => $this->getSetting('button_text'),
          '#description' => $this->getSetting('show_description') ? $item->description : NULL,
          '#written_pronunciation' => $this->getSetting('show_written_pronunciation') ? $item->written_pronunciation : NULL,
          '#attached' => [
            'library' => [
              'name_pronunciation/player',
            ],
          ],
        ];
      }
    }

    return $elements;
  }

}
