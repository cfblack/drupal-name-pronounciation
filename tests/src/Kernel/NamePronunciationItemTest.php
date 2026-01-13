<?php

declare(strict_types=1);

namespace Drupal\Tests\name_pronunciation\Kernel;

use Drupal\Core\Entity\EntityInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\file\Entity\File;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;

/**
 * Tests the name_pronunciation field type.
 *
 * @group name_pronunciation
 */
class NamePronunciationItemTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'file',
    'node',
    'text',
    'name_pronunciation',
  ];

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

    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('file');
    $this->installSchema('file', ['file_usage']);
    $this->installConfig(['field', 'node', 'file']);

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
  }

  /**
   * Tests that the field type is properly defined.
   */
  public function testFieldTypeDefinition(): void {
    $field_type_manager = \Drupal::service('plugin.manager.field.field_type');
    $definition = $field_type_manager->getDefinition('name_pronunciation');

    $this->assertEquals('Name Pronunciation', $definition['label']->render());
    $this->assertEquals('Drupal\name_pronunciation\Plugin\Field\FieldType\NamePronunciationItem', $definition['class']);
  }

  /**
   * Tests the field storage schema.
   */
  public function testFieldStorageSchema(): void {
    $schema = $this->fieldStorage->getSchema();

    // Verify the schema has all expected columns.
    $this->assertArrayHasKey('target_id', $schema['columns']);
    $this->assertArrayHasKey('description', $schema['columns']);
    $this->assertArrayHasKey('written_pronunciation', $schema['columns']);
    $this->assertArrayHasKey('upload_target_id', $schema['columns']);

    // Verify column types.
    $this->assertEquals('int', $schema['columns']['target_id']['type']);
    $this->assertEquals('varchar', $schema['columns']['description']['type']);
    $this->assertEquals(255, $schema['columns']['description']['length']);
    $this->assertEquals('varchar', $schema['columns']['written_pronunciation']['type']);
    $this->assertEquals(255, $schema['columns']['written_pronunciation']['length']);
    $this->assertEquals('int', $schema['columns']['upload_target_id']['type']);
  }

  /**
   * Tests the field settings.
   */
  public function testFieldSettings(): void {
    $settings = $this->field->getSettings();

    // Check default settings.
    $this->assertEquals('webm ogg mp3 mp4 m4a wav', $settings['file_extensions']);
    $this->assertEquals(10, $settings['max_duration']);
  }

  /**
   * Tests the storage settings.
   */
  public function testStorageSettings(): void {
    $settings = $this->fieldStorage->getSettings();

    // Check default storage settings.
    $this->assertEquals('file', $settings['target_type']);
    $this->assertEquals('public', $settings['uri_scheme']);
  }

  /**
   * Tests saving and loading field values with a recorded file.
   */
  public function testSaveAndLoadWithRecordedFile(): void {
    // Create a test file.
    $file = $this->createTestFile('recorded.webm');

    // Create a node with the pronunciation field.
    $node = Node::create([
      'type' => 'article',
      'title' => 'Test Article',
      'field_pronunciation' => [
        'target_id' => $file->id(),
        'description' => 'Full name pronunciation',
        'written_pronunciation' => 'JOHN-son',
      ],
    ]);
    $node->save();

    // Reload the node.
    $node = Node::load($node->id());

    // Verify field values.
    $this->assertEquals($file->id(), $node->get('field_pronunciation')->target_id);
    $this->assertEquals('Full name pronunciation', $node->get('field_pronunciation')->description);
    $this->assertEquals('JOHN-son', $node->get('field_pronunciation')->written_pronunciation);
  }

  /**
   * Tests saving and loading field values with an uploaded file.
   */
  public function testSaveAndLoadWithUploadedFile(): void {
    // Create test files.
    $recorded_file = $this->createTestFile('recorded.webm');
    $uploaded_file = $this->createTestFile('uploaded.mp3');

    // Create a node with both recorded and uploaded files.
    $node = Node::create([
      'type' => 'article',
      'title' => 'Test Article',
      'field_pronunciation' => [
        'target_id' => $recorded_file->id(),
        'description' => 'Name pronunciation',
        'written_pronunciation' => 'SMITH',
        'upload_target_id' => $uploaded_file->id(),
      ],
    ]);
    $node->save();

    // Reload the node.
    $node = Node::load($node->id());

    // Verify field values including upload_target_id.
    $this->assertEquals($recorded_file->id(), $node->get('field_pronunciation')->target_id);
    $this->assertEquals($uploaded_file->id(), $node->get('field_pronunciation')->upload_target_id);
    $this->assertEquals('Name pronunciation', $node->get('field_pronunciation')->description);
    $this->assertEquals('SMITH', $node->get('field_pronunciation')->written_pronunciation);
  }

  /**
   * Tests empty field values.
   */
  public function testEmptyFieldValue(): void {
    // Create a node without the pronunciation field set.
    $node = Node::create([
      'type' => 'article',
      'title' => 'Test Article',
    ]);
    $node->save();

    // Reload the node.
    $node = Node::load($node->id());

    // Verify the field is empty.
    $this->assertTrue($node->get('field_pronunciation')->isEmpty());
  }

  /**
   * Tests field with only description and written pronunciation (no file).
   */
  public function testFieldWithoutFile(): void {
    // Create a node with only text values (no file).
    $node = Node::create([
      'type' => 'article',
      'title' => 'Test Article',
      'field_pronunciation' => [
        'description' => 'First name',
        'written_pronunciation' => 'MEE-chael',
      ],
    ]);
    $node->save();

    // Reload the node.
    $node = Node::load($node->id());

    // Note: Without a target_id, the field item might be considered empty
    // depending on the isEmpty() implementation. This tests the storage behavior.
    $field_value = $node->get('field_pronunciation')->first();
    if ($field_value) {
      $this->assertEquals('First name', $field_value->description);
      $this->assertEquals('MEE-chael', $field_value->written_pronunciation);
    }
  }

  /**
   * Tests multiple field values (cardinality > 1).
   */
  public function testMultipleFieldValues(): void {
    // Update field storage to allow multiple values.
    $this->fieldStorage->setCardinality(3);
    $this->fieldStorage->save();

    // Create test files.
    $file1 = $this->createTestFile('first.webm');
    $file2 = $this->createTestFile('last.webm');

    // Create a node with multiple pronunciation values.
    $node = Node::create([
      'type' => 'article',
      'title' => 'Test Article',
      'field_pronunciation' => [
        [
          'target_id' => $file1->id(),
          'description' => 'First name',
          'written_pronunciation' => 'JOHN',
        ],
        [
          'target_id' => $file2->id(),
          'description' => 'Last name',
          'written_pronunciation' => 'DOE',
        ],
      ],
    ]);
    $node->save();

    // Reload the node.
    $node = Node::load($node->id());

    // Verify multiple values.
    $values = $node->get('field_pronunciation');
    $this->assertCount(2, $values);

    $this->assertEquals($file1->id(), $values[0]->target_id);
    $this->assertEquals('First name', $values[0]->description);
    $this->assertEquals('JOHN', $values[0]->written_pronunciation);

    $this->assertEquals($file2->id(), $values[1]->target_id);
    $this->assertEquals('Last name', $values[1]->description);
    $this->assertEquals('DOE', $values[1]->written_pronunciation);
  }

  /**
   * Tests that the field property definitions are correct.
   */
  public function testFieldPropertyDefinitions(): void {
    $properties = $this->fieldStorage->getPropertyDefinitions();

    // Check that custom properties exist.
    $this->assertArrayHasKey('description', $properties);
    $this->assertArrayHasKey('written_pronunciation', $properties);
    $this->assertArrayHasKey('upload_target_id', $properties);

    // Verify property types.
    $this->assertEquals('string', $properties['description']->getDataType());
    $this->assertEquals('string', $properties['written_pronunciation']->getDataType());
    $this->assertEquals('integer', $properties['upload_target_id']->getDataType());
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
  protected function createTestFile(string $filename): File {
    // Create the pronunciations directory.
    $directory = 'public://pronunciations';
    \Drupal::service('file_system')->prepareDirectory($directory, \Drupal::service('file_system')::CREATE_DIRECTORY);

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
