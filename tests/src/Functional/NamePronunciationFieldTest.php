<?php

declare(strict_types=1);

namespace Drupal\Tests\name_pronunciation\Functional;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\file\Entity\File;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the name_pronunciation field widget and formatter.
 *
 * @group name_pronunciation
 */
class NamePronunciationFieldTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'field_ui',
    'file',
    'node',
    'text',
    'name_pronunciation',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * A user with administrative permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * The field storage configuration.
   *
   * @var \Drupal\field\Entity\FieldStorageConfig
   */
  protected $fieldStorage;

  /**
   * The field configuration.
   *
   * @var \Drupal\field\Entity\FieldConfig
   */
  protected $field;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create a content type.
    NodeType::create([
      'type' => 'article',
      'name' => 'Article',
    ])->save();

    // Create the field storage.
    $this->fieldStorage = FieldStorageConfig::create([
      'field_name' => 'field_pronunciation',
      'entity_type' => 'node',
      'type' => 'name_pronunciation',
    ]);
    $this->fieldStorage->save();

    // Create the field instance.
    $this->field = FieldConfig::create([
      'field_storage' => $this->fieldStorage,
      'bundle' => 'article',
      'label' => 'Name Pronunciation',
    ]);
    $this->field->save();

    // Configure the widget.
    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository */
    $display_repository = \Drupal::service(
      'entity_display.repository'
    );
    $display_repository->getFormDisplay('node', 'article')
      ->setComponent('field_pronunciation', [
        'type' => 'name_pronunciation_recorder',
      ])
      ->save();

    // Configure the formatter.
    $display_repository->getViewDisplay('node', 'article')
      ->setComponent('field_pronunciation', [
        'type' => 'name_pronunciation_player',
        'settings' => [
          'show_description' => TRUE,
          'show_written_pronunciation' => TRUE,
          'button_text' => 'Listen to pronunciation',
        ],
      ])
      ->save();

    // Create an admin user.
    $this->adminUser = $this->drupalCreateUser([
      'administer nodes',
      'create article content',
      'edit any article content',
      'access content',
      'administer content types',
      'administer node fields',
      'administer node form display',
      'administer node display',
    ]);
  }

  /**
   * Tests that the widget form elements are present.
   */
  public function testWidgetFormElements(): void {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('node/add/article');
    $assert = $this->assertSession();

    // Check that the field wrapper is present.
    $assert->fieldExists(
      'field_pronunciation[0][description]'
    );
    $assert->fieldExists(
      'field_pronunciation[0][written_pronunciation]'
    );

    // Check for the hidden audio data field.
    $assert->hiddenFieldExists(
      'field_pronunciation[0][audio_data]'
    );

    // Check for the recorder container.
    $assert->elementExists(
      'css',
      '.name-pronunciation-recorder'
    );

    // Check for recorder controls.
    $assert->buttonExists('Record');
    $assert->buttonExists('Stop');

    // Check for status message container.
    $assert->elementExists('css', '.recorder-status');
  }

  /**
   * Tests saving description and written pronunciation.
   */
  public function testWidgetSaveTextFields(): void {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('node/add/article');

    // Fill in the form.
    $edit = [
      'title[0][value]' => 'Test Node',
      'field_pronunciation[0][description]' => 'First name',
      'field_pronunciation[0][written_pronunciation]' => 'JON',
    ];
    $this->submitForm($edit, 'Save');

    // The node should be saved even without an audio file.
    $this->assertSession()->pageTextContains(
      'has been created'
    );
  }

  /**
   * Tests the formatter output with a file.
   */
  public function testFormatterOutput(): void {
    // Create a test file.
    $file = $this->createTestFile();

    // Create a node with the pronunciation field.
    $node = $this->drupalCreateNode([
      'type' => 'article',
      'title' => 'Test Article',
      'field_pronunciation' => [
        'target_id' => $file->id(),
        'description' => 'Full name',
        'written_pronunciation' => 'TEST-name',
      ],
    ]);

    // View the node.
    $this->drupalGet('node/' . $node->id());
    $assert = $this->assertSession();

    // Check for the audio player.
    $assert->elementExists(
      'css',
      '.name-pronunciation-player'
    );

    // Check for the play button.
    $assert->elementExists(
      'css',
      '.pronunciation-play-button'
    );

    // Check for description display.
    $assert->pageTextContains('Full name');

    // Check for written pronunciation display.
    $assert->pageTextContains('TEST-name');

    // Check for the audio element.
    $assert->elementExists('css', 'audio');
  }

  /**
   * Tests formatter settings.
   */
  public function testFormatterSettings(): void {
    // Create a test file.
    $file = $this->createTestFile();

    // Create a node with the pronunciation field.
    $node = $this->drupalCreateNode([
      'type' => 'article',
      'title' => 'Test Article',
      'field_pronunciation' => [
        'target_id' => $file->id(),
        'description' => 'Hidden description',
        'written_pronunciation' => 'Hidden pron',
      ],
    ]);

    // Hide description and written pronunciation.
    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository */
    $display_repository = \Drupal::service(
      'entity_display.repository'
    );
    $display_repository->getViewDisplay('node', 'article')
      ->setComponent('field_pronunciation', [
        'type' => 'name_pronunciation_player',
        'settings' => [
          'show_description' => FALSE,
          'show_written_pronunciation' => FALSE,
          'button_text' => 'Custom button text',
        ],
      ])
      ->save();

    // Clear caches to apply new settings.
    drupal_flush_all_caches();

    // View the node.
    $this->drupalGet('node/' . $node->id());
    $assert = $this->assertSession();

    // Player should still exist.
    $assert->elementExists(
      'css',
      '.name-pronunciation-player'
    );

    // Description should be hidden.
    $assert->pageTextNotContains('Hidden description');

    // Written pronunciation should be hidden.
    $assert->pageTextNotContains('Hidden pron');
  }

  /**
   * Tests uploaded file takes priority over recorded.
   */
  public function testUploadedFilePriority(): void {
    // Create two test files.
    $recorded_file = $this->createTestFile('recorded.webm');
    $uploaded_file = $this->createTestFile('uploaded.mp3');

    // Create a node with both files.
    $node = $this->drupalCreateNode([
      'type' => 'article',
      'title' => 'Test Article',
      'field_pronunciation' => [
        'target_id' => $recorded_file->id(),
        'upload_target_id' => $uploaded_file->id(),
        'description' => 'Test',
        'written_pronunciation' => 'TEST',
      ],
    ]);

    // View the node.
    $this->drupalGet('node/' . $node->id());
    $assert = $this->assertSession();

    // Check that audio element exists.
    $assert->elementExists('css', 'audio source');

    // The uploaded file should be used.
    $page_source = $this->getSession()
      ->getPage()->getContent();
    $this->assertStringContainsString(
      'uploaded.mp3',
      $page_source
    );
  }

  /**
   * Tests empty field display.
   */
  public function testEmptyFieldDisplay(): void {
    // Create a node without the pronunciation field.
    $node = $this->drupalCreateNode([
      'type' => 'article',
      'title' => 'Test Article Without Pronunciation',
    ]);

    // View the node.
    $this->drupalGet('node/' . $node->id());
    $assert = $this->assertSession();

    // Player should not exist.
    $assert->elementNotExists(
      'css',
      '.name-pronunciation-player'
    );
  }

  /**
   * Tests the widget settings form on form display page.
   */
  public function testWidgetSettingsForm(): void {
    $this->drupalLogin($this->adminUser);

    // Visit the form display page.
    $this->drupalGet(
      'admin/structure/types/manage/article/form-display'
    );
    $assert = $this->assertSession();

    // Check that the pronunciation field is listed.
    $assert->pageTextContains('Name Pronunciation');
  }

  /**
   * Tests the formatter settings form on display page.
   */
  public function testFormatterSettingsForm(): void {
    $this->drupalLogin($this->adminUser);

    // Visit the display page.
    $this->drupalGet(
      'admin/structure/types/manage/article/display'
    );
    $assert = $this->assertSession();

    // Check that the pronunciation field is listed.
    $assert->pageTextContains('Name Pronunciation');
  }

  /**
   * Tests the recorder library is attached to the widget.
   */
  public function testRecorderLibraryAttached(): void {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('node/add/article');

    // Check that the recorder JS is attached.
    $page_source = $this->getSession()
      ->getPage()->getContent();
    $this->assertStringContainsString(
      'js/recorder.js',
      $page_source
    );
  }

  /**
   * Tests the player library is attached to formatter.
   */
  public function testPlayerLibraryAttached(): void {
    // Create a test file.
    $file = $this->createTestFile();

    // Create a node with the pronunciation field.
    $node = $this->drupalCreateNode([
      'type' => 'article',
      'title' => 'Test Article',
      'field_pronunciation' => [
        'target_id' => $file->id(),
        'description' => 'Test',
        'written_pronunciation' => 'TEST',
      ],
    ]);

    // View the node.
    $this->drupalGet('node/' . $node->id());

    // Check that the player JS is attached.
    $page_source = $this->getSession()
      ->getPage()->getContent();
    $this->assertStringContainsString(
      'js/player.js',
      $page_source
    );
  }

  /**
   * Tests field is available in manage fields.
   */
  public function testFieldAvailableInUi(): void {
    $this->drupalLogin($this->adminUser);

    // Visit the manage fields page for article.
    $this->drupalGet(
      'admin/structure/types/manage/article/fields'
    );
    $assert = $this->assertSession();

    // The field should be listed.
    $assert->pageTextContains('Name Pronunciation');
  }

  /**
   * Creates a test file entity.
   *
   * @param string $filename
   *   The filename for the test file.
   *
   * @return \Drupal\file\Entity\File
   *   The created file entity.
   */
  protected function createTestFile(
    string $filename = 'test.webm',
  ): File {
    // Create the pronunciations directory.
    $directory = 'public://pronunciations';
    \Drupal::service('file_system')
      ->prepareDirectory(
        $directory,
        \Drupal::service('file_system')::CREATE_DIRECTORY
      );

    // Create a simple test file.
    $uri = $directory . '/' . $filename;
    file_put_contents($uri, 'test audio content');

    $file = File::create([
      'uri' => $uri,
      'filename' => $filename,
      'status' => 1,
    ]);
    $file->save();

    return $file;
  }

}
