<?php
/**
 * @file
 * Tests for the SelectOrOtherWidgetBase class.
 */

namespace Drupal\tests\select_or_other\Unit;

use Drupal\Core\Form\FormState;
use Drupal\select_or_other\Plugin\Field\FieldWidget\SelectOrOtherWidgetBase;
use Drupal\Tests\UnitTestCase;
use PHPUnit_Framework_MockObject_MockObject;
use PHPUnit_Framework_MockObject_Stub_ConsecutiveCalls;
use ReflectionMethod;

/**
 * Tests the form element implementation.
 *
 * @group select_or_other
 *
 * @covers Drupal\select_or_other\Plugin\Field\FieldWidget\SelectOrOtherWidgetBase
 */
class SelectOrOtherWidgetBaseTest extends UnitTestCase {

  /* @var string $testedClassName */
  protected $testedClassName;

  /* @var PHPUnit_Framework_MockObject_MockObject $stub */
  protected $widgetBaseMock;

  /* @var PHPUnit_Framework_MockObject_MockObject $fieldDefinition */
  protected $fieldDefinition;

  /* @var PHPUnit_Framework_MockObject_MockObject $containerMock */
  protected $containerMock;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->testedClassName = 'Drupal\select_or_other\Plugin\Field\FieldWidget\SelectOrOtherWidgetBase';
    $container_class = 'Drupal\Core\DependencyInjection\Container';
    $methods = get_class_methods($container_class);
    $this->containerMock = $this->getMockBuilder($container_class)
      ->disableOriginalConstructor()
      ->setMethods($methods)
      ->getMock();
    \Drupal::setContainer($this->containerMock);

    $this->fieldDefinition = $this->getMockForAbstractClass('\Drupal\Core\Field\FieldDefinitionInterface');
    $arguments = [
      '',
      '',
      $this->fieldDefinition,
      [],
      [],
    ];

    $this->widgetBaseMock = $this->getMockForAbstractClass($this->testedClassName, $arguments);
    /** @var SelectOrOtherWidgetBase $mock */
    $mock = $this->widgetBaseMock;
    $mock->setStringTranslation($this->getStringTranslationStub());
    $mock->setSettings([]);

  }

  /**
   * Tests functionality of SelectOrOtherWidgetBase::settingsForm.
   */
  public function testSettingsForm() {
    $dummy_form = [];
    $dummy_state = new FormState();
    $expected_keys = [
      '#title',
      '#type',
      '#options',
      '#default_value',
    ];

    $element_key = 'select_element_type';
    $options = ['select_or_other_select', 'select_or_other_buttons'];
    /** @var SelectOrOtherWidgetBase $mock */
    $mock = $this->widgetBaseMock;
    foreach ($options as $option) {
      $mock->setSetting($element_key, $option);
      $form = $mock->settingsForm($dummy_form, $dummy_state);
      $this->assertArrayEquals($expected_keys, array_keys($form[$element_key]), 'Settings form has the expected keys');
      $this->assertArrayEquals($options, array_keys($form[$element_key]['#options']), 'Settings form has the expected options.');
      $this->assertEquals($option, $form[$element_key]['#default_value'], 'default value is correct.');
    }
  }

  /**
   * Tests the functionality of SelectOrOtherWidgetBase::settingsSummary.
   */
  public function testSettingsSummary() {
    /** @var SelectOrOtherWidgetBase $mock */
    $mock = $this->widgetBaseMock;
    $element_type_options = new ReflectionMethod($this->testedClassName, 'selectElementTypeOptions');
    $element_type_options->setAccessible(TRUE);

    $options = $element_type_options->invoke($mock);
    $element_type_label = '';
    foreach ($options as $option => $label) {
      $element_type_label = $label;
      $mock->setSetting('select_element_type', $option);

      $expected = ['Type of select form element: ' . $label];
      $summary = $mock->settingsSummary();

      $this->assertArrayEquals($expected, $summary);
    }

    $sort_options = new ReflectionMethod($this->testedClassName, 'sortOptions');
    $sort_options->setAccessible(TRUE);
    $options = $sort_options->invoke($mock);
    foreach ($options as $option => $label) {
      $mock->setSetting('sort_options', $option);

      $expected = ['Type of select form element: ' . $element_type_label];
      if ($option !== '') {
        $expected[] = $label;
      }
      $summary = $mock->settingsSummary();

      $this->assertArrayEquals($expected, $summary);
    }
  }

  /**
   * Tests the functionality of several small helper methods.
   */
  public function testHelperMethods() {
    $storage_stub = $this->getMockForAbstractClass('\Drupal\Core\Field\FieldStorageDefinitionInterface');
    $storage_stub->method('isMultiple')
      ->will($this->onConsecutiveCalls(TRUE, FALSE));
    $this->fieldDefinition->method('getFieldStorageDefinition')
      ->willReturn($storage_stub);
    $this->fieldDefinition->method('isRequired')
      ->will($this->onConsecutiveCalls(TRUE, FALSE));

    $is_multiple = new ReflectionMethod($this->testedClassName, 'isMultiple');
    $is_multiple->setAccessible(TRUE);
    $this->assertTrue($is_multiple->invoke($this->widgetBaseMock));
    $this->assertFalse($is_multiple->invoke($this->widgetBaseMock));

    $is_required = new ReflectionMethod($this->testedClassName, 'isRequired');
    $is_required->setAccessible(TRUE);
    $this->assertTrue($is_required->invoke($this->widgetBaseMock));
    $this->assertFalse($is_required->invoke($this->widgetBaseMock));

  }


  /**
   * Tests the functionality of SelectOrOtherWidgetBase::getSelectedOptions.
   */
  public function testGetSelectedOptions() {
    // Mock the widget.
    $mock = $this->getMockBuilder('Drupal\select_or_other\Plugin\Field\FieldWidget\SelectOrOtherWidgetBase')
      ->disableOriginalConstructor()
      ->setMethods(['getColumn', 'getOptions'])
      ->getMockForAbstractClass();
    $mock->method('getColumn')->willReturn('id');
    $mock->method('getOptions')
      ->willReturnOnConsecutiveCalls([], [1 => 1, 2 => 2, 3 => 3]);

    // Mock up some entities.
    $entity1 = $this->getMockForAbstractClass('Drupal\Core\Entity\EntityInterface');
    $entity1->id = 1;
    $entity2 = $this->getMockForAbstractClass('Drupal\Core\Entity\EntityInterface');
    $entity2->id = 3;

    // Put the entities in a mocked list.
    $items = $this->getMockForAbstractClass('Drupal\Core\Field\FieldItemListInterface');
    $items->method('valid')
      ->willReturnOnConsecutiveCalls(TRUE, TRUE, FALSE, TRUE, TRUE, FALSE);
    $items->method('current')
      ->willReturnOnConsecutiveCalls($entity1, $entity2, $entity1, $entity2);

    // Make getSelectedOptions accessible.
    $get_selected_options = new ReflectionMethod($this->testedClassName, 'getSelectedOptions');
    $get_selected_options->setAccessible(TRUE);

    $expected = [];
    $selected_options = $get_selected_options->invokeArgs($mock, [$items]);
    $this->assertArrayEquals($expected, $selected_options, 'Selected options without a matching option are filtered out.');

    $expected = [1, 3];
    $selected_options = $get_selected_options->invokeArgs($mock, [$items]);
    $this->assertArrayEquals($expected, $selected_options, 'Selected options with matching options are kept.');
  }

  /**
   * Make sure the addOptionsToElement adds options as expected.
   */
  public function testAddOptions() {
    // Mock the widget.
    $mock = $this->getMockBuilder('Drupal\select_or_other\Plugin\Field\FieldWidget\SelectOrOtherWidgetBase')
      ->disableOriginalConstructor()
      ->setMethods(['getOptions', 'getSetting'])
      ->getMockForAbstractClass();
    $mock->method('getOptions')
      ->willReturnOnConsecutiveCalls([], ['a'], ['a', 'b'], ['key' => 'value']);

    // Make getSelectedOptions accessible.
    $add_options_to_element = new ReflectionMethod($this->testedClassName, 'addOptionsToElement');
    $add_options_to_element->setAccessible(TRUE);

    $element = [];
    $expected = ['#options' => []];
    $args = [& $element];
    $add_options_to_element->invokeArgs($mock, $args);
    $this->assertArrayEquals($expected, $element);

    $expected['#options'] = ['a'];
    $add_options_to_element->invokeArgs($mock, $args);
    $this->assertArrayEquals($expected, $element);

    $expected['#options'] = ['a', 'b'];
    $add_options_to_element->invokeArgs($mock, $args);
    $this->assertArrayEquals($expected, $element);

    $expected['#options'] = ['key' => 'value'];
    $add_options_to_element->invokeArgs($mock, $args);
    $this->assertArrayEquals($expected, $element);
  }


  /**
   * Make sure the addOptionsToElement method sorts the options properly.
   */
  public function testAddOptionsSorting() {
    $options = ['a', 'z', 'k'];
    // Mock the widget.
    $mock = $this->getMockBuilder('Drupal\select_or_other\Plugin\Field\FieldWidget\SelectOrOtherWidgetBase')
      ->disableOriginalConstructor()
      ->setMethods(['getOptions', 'getSetting'])
      ->getMockForAbstractClass();
    $mock->method('getOptions')->willReturn($options);
    $mock->method('getSetting')
      ->with('sort_options')
      ->willReturnCallback(get_class($this) . '::sortOptionsDirectionCallback');

    // Make getSelectedOptions accessible.
    $add_options_to_element = new ReflectionMethod($this->testedClassName, 'addOptionsToElement');
    $add_options_to_element->setAccessible(TRUE);

    $element = [];
    $expected = ['#options' => $options];
    $args = [& $element];
    $add_options_to_element->invokeArgs($mock, $args);
    $this->assertSame($expected, $element, 'Options are unsorted when sorting has not been enabled.');

    $expected['#options'] = $options;
    uasort($expected['#options'], 'strcasecmp');
    $add_options_to_element->invokeArgs($mock, $args);
    $this->assertSame($expected, $element, 'Options are sorted ascending if configured as such.');

    uasort($expected['#options'], function ($a, $b) {
      return -1 * strcasecmp($a, $b);
    });
    $add_options_to_element->invokeArgs($mock, $args);
    $this->assertSame($expected, $element, 'Options are sorted descending if configured as such.');

    $expected['#options'] = $options;
    $add_options_to_element->invokeArgs($mock, $args);
    $this->assertSame($expected, $element, 'Options are unsorted when an invalid direction was passed.');
  }

  /**
   * Callback for dummy sort options settings.
   *
   * @return string
   */
  public static function sortOptionsDirectionCallback() {
    static $count;
    $count++;

    switch ($count) {
      case 1:
        return '';

      case 2:
        return 'ASC';

      case 3:
        return 'DESC';
    }

    $count = 0;
    return 'invalid direction';
  }
}
