<?php

namespace Drupal\tests\select_or_other\Unit;

use Drupal\Core\Form\FormState;
use Drupal\select_or_other\Plugin\Field\FieldWidget\SelectOrOtherWidgetBase;
use Drupal\Tests\UnitTestCase;
use PHPUnit_Framework_MockObject_MockObject;
use ReflectionMethod;

/**
 * Tests the form element implementation.
 *
 * @group select_or_other
 * @covers Drupal\select_or_other\Plugin\Field\FieldWidget\SelectOrOtherWidgetBase
 */
class SelectOrOtherWidgetBaseTest extends UnitTestCase {

  protected static $testedClassName = 'Drupal\select_or_other\Plugin\Field\FieldWidget\SelectOrOtherWidgetBase';

  /**
   * @var SelectOrOtherWidgetBase $stub
   */
  protected $stub;

  /**
   * @var PHPUnit_Framework_MockObject_MockObject $containerMock
   */
  protected $containerMock;

  protected function setUp() {
    parent::setUp();
    $container_class = 'Drupal\Core\DependencyInjection\Container';
    $methods = get_class_methods($container_class);
    $this->containerMock = $this->getMockBuilder($container_class)
      ->disableOriginalConstructor()
      ->setMethods($methods)
      ->getMock();
    \Drupal::setContainer($this->containerMock);

    $this->stub = $this->getMockForAbstractClass($this::$testedClassName, [], '', FALSE);
    $this->stub->setStringTranslation($this->getStringTranslationStub());
    $this->stub->setSettings([]);

  }

  /**
   * Test if defaultSettings() returns the correct keys.
   */
  public function testDefaultSettings() {
    $expected_keys = [
      'select_element_type',
      'available_options',
      'other',
      'other_title',
      'other_unknown_defaults',
      'other_size',
      'sort_options',
    ];

    $actual_keys = array_keys(SelectOrOtherWidgetBase::defaultSettings());
    $this->assertArrayEquals($expected_keys, $actual_keys);
  }

  /**
   * Tests functionality of SelectOrOtherWidgetBase::settingsForm
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
    foreach ($options as $option) {
      $this->stub->setSetting($element_key, $option);
      $form = $this->stub->settingsForm($dummy_form, $dummy_state);
      $this->assertArrayEquals($expected_keys, array_keys($form[$element_key]), 'Settings form has the expected keys');
      $this->assertArrayEquals($options, array_keys($form[$element_key]['#options']), 'Settings form has the expected options.');
      $this->assertEquals($option, $form[$element_key]['#default_value'], 'default value is correct.');
    }
  }

  /**
   * Tests the functionality of SelectOrOtherWidgetBase::settingsSummary
   */
  public function testSettingsSummary() {
    $elementTypeOptions = new ReflectionMethod($this::$testedClassName, 'selectElementTypeOptions');
    $elementTypeOptions->setAccessible(TRUE);
    $options = $elementTypeOptions->invoke($this->stub);
    $selected_option = 'select_or_other_select';
    $this->stub->setSetting('select_element_type', $selected_option);

    $expected[] = 'Type of select form element: ' . $options[$selected_option];
    $summary = $this->stub->settingsSummary();

    $this->assertArrayEquals($expected, $summary);
  }

}
