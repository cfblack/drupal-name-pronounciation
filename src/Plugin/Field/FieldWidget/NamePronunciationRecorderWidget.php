<?php

namespace Drupal\name_pronunciation\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a widget for recording name pronunciations.
 *
 * @FieldWidget(
 *   id = "name_pronunciation_recorder",
 *   label = @Translation("Audio Recorder"),
 *   field_types = {
 *     "name_pronunciation"
 *   }
 * )
 */
class NamePronunciationRecorderWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    // Wrap all pronunciation fields in a fieldset.
    $element['#type'] = 'fieldset';
    $element['#title'] = $this->t('Pronunciation');
    $element['#attached']['library'][] = 'name_pronunciation/recorder';

    $element['target_id'] = [
      '#type' => 'hidden',
      '#default_value' => $items[$delta]->target_id ?? NULL,
    ];

    $element['description'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Description'),
      '#default_value' => $items[$delta]->description ?? '',
      '#description' => $this->t('Optional description (e.g., "First name", "Last name")'),
      '#maxlength' => 255,
    ];

    // Add file upload field.
    $element['upload_target_id'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Upload audio file'),
      '#description' => $this->t('Upload a pre-recorded audio file instead of recording. This will be used instead of a recorded pronunciation if provided.'),
      '#default_value' => !empty($items[$delta]->upload_target_id) ? [$items[$delta]->upload_target_id] : NULL,
      '#upload_location' => 'public://pronunciations',
      '#upload_validators' => [
        'file_validate_extensions' => [$this->getFieldSetting('file_extensions')],
      ],
    ];

    $element['recorder'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['name-pronunciation-recorder'],
        'data-field-name' => $items->getName(),
        'data-delta' => $delta,
      ],
    ];

    $element['recorder']['controls'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['recorder-controls']],
    ];

    $element['recorder']['controls']['record'] = [
      '#type' => 'button',
      '#value' => $this->t('Record'),
      '#attributes' => [
        'class' => ['recorder-button', 'recorder-record'],
        'data-action' => 'record',
      ],
    ];

    $element['recorder']['controls']['stop'] = [
      '#type' => 'button',
      '#value' => $this->t('Stop'),
      '#attributes' => [
        'class' => ['recorder-button', 'recorder-stop', 'hidden'],
        'data-action' => 'stop',
      ],
    ];

    $element['recorder']['status'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['recorder-status']],
      '#markup' => '<span class="status-message"></span>',
    ];

    $element['recorder']['preview'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['recorder-preview', 'hidden']],
    ];

    // If there's an existing uploaded file, show it.
    if (!empty($items[$delta]->upload_target_id)) {
      /** @var \Drupal\file\FileInterface $file */
      $file = \Drupal::entityTypeManager()->getStorage('file')->load($items[$delta]->upload_target_id);
      if ($file) {
        $element['current_upload'] = [
          '#type' => 'container',
          '#attributes' => ['class' => ['current-pronunciation-upload']],
        ];

        $element['current_upload']['label'] = [
          '#markup' => '<strong>' . $this->t('Current uploaded file:') . '</strong>',
        ];

        $element['current_upload']['audio'] = [
          '#theme' => 'name_pronunciation_audio_player',
          '#audio_url' => $file->createFileUrl(),
          '#file_mime_type' => $file->getMimeType(),
          '#attached' => [
            'library' => [
              'name_pronunciation/player',
            ],
          ],
        ];
      }
    }

    // If there's an existing recording, show it.
    if (!empty($items[$delta]->target_id)) {
      /** @var \Drupal\file\FileInterface $file */
      $file = $items[$delta]->entity;
      if ($file) {
        $element['current'] = [
          '#type' => 'container',
          '#attributes' => ['class' => ['current-pronunciation']],
        ];

        $element['current']['label'] = [
          '#markup' => '<strong>' . $this->t('Current recording:') . '</strong>',
        ];

        $element['current']['audio'] = [
          '#theme' => 'name_pronunciation_audio_player',
          '#audio_url' => $file->createFileUrl(),
          '#file_mime_type' => $file->getMimeType(),
          '#attached' => [
            'library' => [
              'name_pronunciation/player',
            ],
          ],
        ];
      }
    }

    $element['audio_data'] = [
      '#type' => 'hidden',
      '#attributes' => ['class' => ['audio-data-field']],
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    foreach ($values as &$value) {
      // Handle the uploaded file.
      if (!empty($value['upload_target_id'])) {
        // The managed_file element returns an array with the file ID.
        if (is_array($value['upload_target_id'])) {
          $value['upload_target_id'] = reset($value['upload_target_id']);
        }
      }
      else {
        $value['upload_target_id'] = NULL;
      }

      // Handle the audio data and create a file entity.
      if (!empty($value['audio_data'])) {
        $audio_data = $value['audio_data'];
        // The audio data will be base64 encoded from JavaScript.
        if (preg_match('/^data:audio\/(\w+);base64,(.+)$/', $audio_data, $matches)) {
          $extension = $matches[1];
          $data = base64_decode($matches[2]);

          // Create a unique filename.
          $filename = 'pronunciation_' . time() . '_' . uniqid() . '.' . $extension;
          $directory = 'public://pronunciations';

          // Ensure directory exists.
          \Drupal::service('file_system')->prepareDirectory($directory, \Drupal\Core\File\FileSystemInterface::CREATE_DIRECTORY);

          // Save the file.
          $file = \Drupal::service('file.repository')->writeData($data, $directory . '/' . $filename, \Drupal\Core\File\FileSystemInterface::EXISTS_REPLACE);

          if ($file) {
            $value['target_id'] = $file->id();
          }
        }

        unset($value['audio_data']);
      }

      // Clean up form elements that shouldn't be saved.
      unset($value['recorder']);
      unset($value['current']);
      unset($value['current_upload']);
    }

    return $values;
  }

}
