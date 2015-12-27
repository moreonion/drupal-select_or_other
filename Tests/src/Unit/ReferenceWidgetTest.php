<?php
/**
 * @file
 * Contains unit tests for the ReferenceWidget.
 */

namespace Drupal\Tests\select_or_other\Unit;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormState;
use Drupal\select_or_other\Plugin\Field\FieldWidget\EntityReference\ReferenceWidget;
use Drupal\select_or_other\Plugin\Field\FieldWidget\SelectOrOtherWidgetBase;
use Drupal\Tests\UnitTestCase;
use PHPUnit_Framework_MockObject_MockBuilder;
use PHPUnit_Framework_MockObject_MockObject;
use ReflectionMethod;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Tests the form element implementation.
 *
 * @group select_or_other
 *
 * @covers Drupal\select_or_other\Plugin\Field\FieldWidget\EntityReference\ReferenceWidget
 */
class ReferenceWidgetTest extends UnitTestCase {

  protected $testedClassName;

  /**
   * @var PHPUnit_Framework_MockObject_MockBuilder $stub
   */
  protected $mockBuilder;

  /**
   * @var PHPUnit_Framework_MockObject_MockObject $containerMock
   */
  protected $containerMock;

  protected function setUp() {
    parent::setUp();
    $this->testedClassName = 'Drupal\select_or_other\Plugin\Field\FieldWidget\EntityReference\ReferenceWidget';
    $container_class = 'Drupal\Core\DependencyInjection\Container';
    $methods = get_class_methods($container_class);
    /** @var ContainerInterface $container */
    $this->containerMock = $container = $this->getMockBuilder($container_class)
      ->disableOriginalConstructor()
      ->setMethods($methods)
      ->getMock();
    \Drupal::setContainer($container);

    $this->mockBuilder = $this->getMockBuilder($this->testedClassName);
  }

  /**
   * Test if defaultSettings() returns the correct keys.
   */
  public function testGetOptions() {
    $entity_id = 1;
    $entity_label = 'Label';
    $entity_mock = $this->getMockBuilder('\Drupal\Core\Entity\Entity')
      ->disableOriginalConstructor()
      ->getMock();
    $entity_mock->expects($this->exactly(1))
      ->method('id')
      ->willReturn($entity_id);
    $entity_mock->expects($this->exactly(2))
      ->method('label')
      ->willReturn($entity_label);

    $entity_storage_mock = $this->getMockForAbstractClass('\Drupal\Core\Entity\EntityStorageInterface');
    $entity_storage_mock->expects($this->exactly(2))
      ->method('loadByProperties')
      ->willReturnOnConsecutiveCalls([], [$entity_mock]);

    $mock = $this->mockBuilder->disableOriginalConstructor()
      ->setMethods([
        'getEntityStorage',
        'getBundleKey',
        'getSelectionHandlerSetting'
      ])
      ->getMock();
    $mock->expects($this->exactly(2))
      ->method('getEntityStorage')
      ->willReturn($entity_storage_mock);
    $mock->expects($this->exactly(2))
      ->method('getBundleKey')
      ->willReturn('bundle');
    $mock->expects($this->exactly(2))
      ->method('getSelectionHandlerSetting')
      ->willReturn('target_bundle');

    $get_options = new ReflectionMethod($mock, 'getOptions');
    $get_options->setAccessible(TRUE);

    // First invocation returns an empty array because there are no entities.
    $options = $get_options->invoke($mock);
    $expected = [];
    $this->assertArrayEquals($options, $expected);

    // Second invocation returns a key=>value array because there is one entity.
    $options = $get_options->invoke($mock);
    $expected = ["{$entity_label} ({$entity_id})" => $entity_label];
    $this->assertArrayEquals($options, $expected);
  }

  /**
   * Prepares a mock form element.
   *
   * @param string $target_type
   *   The target type to be returned by the mocked field settings.
   * @param bool|FALSE $tested_class_name
   *   Fully qualified class name to build a mock for.
   *
   * @return \PHPUnit_Framework_MockObject_MockObject
   *   A new mock.
   */
  protected function prepareFormElementMock($target_type = 'entity', $tested_class_name = FALSE) {
    $methods = [
      'getColumn',
      'getOptions',
      'getSelectedOptions',
      'getFieldSetting',
      'getAutoCreateBundle'
    ];

    // Get the mockBuilder
    if ($tested_class_name) {
      $builder = $this->getMockBuilder($tested_class_name);
    }
    else {
      $builder = $this->mockBuilder;
    }

    // Configure the mockBuilder.
    $field_definition = $this->getMockForAbstractClass('\Drupal\Core\Field\FieldDefinitionInterface');
    $field_definition->expects($this->any())
      ->method('getFieldStorageDefinition')
      ->willReturn($this->getMockForAbstractClass('Drupal\Core\Field\FieldStorageDefinitionInterface'));
    $constructor_arguments = ['', '', $field_definition, [], []];

    $builder->setConstructorArgs($constructor_arguments)->setMethods($methods);

    if ($tested_class_name) {
      $class = new \ReflectionClass($tested_class_name);
      $mock = $class->isAbstract() ? $builder->getMockForAbstractClass() : $builder->getMock();
    }
    else {
      $mock = $builder->getMock();
    }

    // Configure the mock.
    $mock->expects($this->any())->method('getColumn')->willReturn('column');
    $mock->expects($this->any())->method('getOptions')->willReturn([]);
    $mock->expects($this->any())->method('getSelectedOptions')->willReturn([]);
    $mock->expects($this->any())
      ->method('getFieldSetting')
      ->willReturnOnConsecutiveCalls($target_type, 'some_handler', [], $target_type);
    $mock->expects($this->any())
      ->method('getAutoCreateBundle')
      ->willReturn('autoCreateBundle');

    return $mock;
  }

  /**
   * Test if formElement() adds the expected information.
   */
  public function testFormElement() {
    $user_mock = $this->getMock('User', ['id']);
    $user_mock->expects($this->any())->method('id')->willReturn(1);
    $this->containerMock->expects($this->any())
      ->method('get')
      ->with('current_user')
      ->willReturn($user_mock);
    foreach (['node', 'taxonomy_term'] as $target_type) {
      /** @var ReferenceWidget $mock */
      $mock = $this->prepareFormElementMock($target_type);
      /** @var SelectOrOtherWidgetBase $parent */
      $parent = $this->prepareFormElementMock($target_type, 'Drupal\select_or_other\Plugin\Field\FieldWidget\SelectOrOtherWidgetBase');

      $entity_class = $target_type === 'taxonomy_term' ? 'Drupal\Core\Entity\EntityInterface' : 'Drupal\user\EntityOwnerInterface';
      $entity = $this->getMockForAbstractClass($entity_class);
      $entity->expects($this->any())->method('getOwnerId')->willReturn(1);
      $items = $this->getMockForAbstractClass('Drupal\Core\Field\FieldItemListInterface');
      $items->expects($this->any())->method('getEntity')->willReturn($entity);
      /** @var FieldItemListInterface $items */
      $delta = 1;
      $element = [];
      $form = [];
      $form_state = new FormState();

      $parent_result = $parent->formElement($items, $delta, $element, $form, $form_state);
      $result = $mock->formElement($items, $delta, $element, $form, $form_state);
      $added = array_diff_key($result, $parent_result);

      $expected = [
        '#target_type' => $target_type,
        '#selection_handler' => 'some_handler',
        '#selection_settings' => [],
        '#autocreate' => [
          'bundle' => 'autoCreateBundle',
          'uid' => 1,
        ],
        '#validate_reference' => TRUE,
        '#tags' => $target_type === 'taxonomy_term',
        '#merged_values' => TRUE,
      ];
      $this->assertArrayEquals($expected, $added);
    }
  }

  /**
   * Tests preparation for EntityAutocomplete::validateEntityAutocomplete.
   */
  public function testPrepareElementValuesForValidation() {
    $method = new ReflectionMethod($this->testedClassName, 'prepareElementValuesForValidation');
    $method->setAccessible(TRUE);

    foreach ([FALSE, TRUE] as $tags) {
      $element = $original_element = [
        '#tags' => $tags,
        '#value' => [
          'Some value',
          'Another value',
        ],
      ];
      $method->invokeArgs(NULL, [ & $element]);

      if ($tags) {
        $this->assertTrue(is_string($element['#value']));
      }
      else {
        $this->assertArrayEquals($original_element, $element);
      }
    }
  }

  /**
   * Tests if the widget correctly determines if it is applicable.
   */
  public function testIsApplicable() {
    $entity_reference_selection = $this->getMockBuilder('Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManager')
      ->disableOriginalConstructor()
      ->getMock();
    $entity_reference_selection->expects($this->exactly(2))
      ->method('getInstance')
      ->willReturnOnConsecutiveCalls(
        $this->getMockForAbstractClass('Drupal\Core\Entity\EntityReferenceSelection\SelectionInterface'),
        $this->getMockForAbstractClass('Drupal\Core\Entity\EntityReferenceSelection\SelectionWithAutocreateInterface')
      );
    $this->containerMock->expects($this->any())
      ->method('get')
      ->with('plugin.manager.entity_reference_selection')
      ->willReturn($entity_reference_selection);

    $definition = $this->getMockBuilder('Drupal\Core\Field\FieldDefinitionInterface')
      ->getMockForAbstractClass();
    $definition->expects($this->exactly(2))
      ->method('getSettings')
      ->willReturn(['handler_settings' => ['auto_create' => TRUE]]);
    /** @var \Drupal\Core\Field\FieldDefinitionInterface $definition */
    $this->assertFalse(ReferenceWidget::isApplicable($definition));
    $this->assertTrue(ReferenceWidget::isApplicable($definition));
  }

  /**
   * Tests if the selected options are propery prepared.
   */
  public function testPrepareSelectedOptions() {
    $entity_id = 1;
    $entity_label = 'Label';
    $entity_mock = $this->getMockBuilder('\Drupal\Core\Entity\Entity')
      ->disableOriginalConstructor()
      ->getMock();
    $entity_mock->expects($this->any())
      ->method('id')
      ->willReturn($entity_id);
    $entity_mock->expects($this->any())
      ->method('label')
      ->willReturn($entity_label);

    $entity_storage_mock = $this->getMockForAbstractClass('\Drupal\Core\Entity\EntityStorageInterface');
    $entity_storage_mock->expects($this->exactly(2))
      ->method('loadMultiple')
      ->willReturnOnConsecutiveCalls([], [$entity_mock]);

    $mock = $this->mockBuilder->disableOriginalConstructor()
      ->setMethods(['getEntityStorage'])
      ->getMock();
    $mock->expects($this->exactly(2))
      ->method('getEntityStorage')
      ->willReturn($entity_storage_mock);

    $get_options = new ReflectionMethod($mock, 'prepareSelectedOptions');
    $get_options->setAccessible(TRUE);

    // First invocation returns an empty array because there are no entities.
    $options = $get_options->invokeArgs($mock, [[]]);
    $expected = [];
    $this->assertArrayEquals($options, $expected);

    // Second invocation returns a value array..
    $options = $get_options->invokeArgs($mock, [[]]);
    $expected = ["{$entity_label} ({$entity_id})"];
    $this->assertArrayEquals($options, $expected);
  }

}
