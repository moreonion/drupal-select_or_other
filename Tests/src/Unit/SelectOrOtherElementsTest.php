<?php

namespace Drupal\Tests\select_or_other\Unit;

use Drupal\Core\Form\FormState;
use Drupal\select_or_other\Element\SelectOrOtherButtons;
use Drupal\select_or_other\Element\SelectOrOtherElementBase;
use Drupal\select_or_other\Element\SelectOrOtherSelect;
use Drupal\Tests\UnitTestCase;
use ReflectionMethod;

/**
 * Tests the form element implementation.
 *
 * @group select_or_other
 * @covers \Drupal\select_or_other\Element\SelectOrOtherElementBase
 * @covers \Drupal\select_or_other\Element\SelectOrOtherButtons
 * @covers \Drupal\select_or_other\Element\SelectOrOtherSelect
 */
class SelectOrOtherElementsTest extends UnitTestCase {

  /**
   * Tests the addition of the other option to an options array.
   */
  public function testAddOtherOption() {
    $options = [];

    // Make the protected method accessible and invoke it.
    $method = new ReflectionMethod('Drupal\select_or_other\Element\SelectOrOtherElementBase', 'addOtherOption');
    $method->setAccessible(TRUE);
    $options = $method->invoke(NULL, $options);

    $this->assertArrayEquals(['select_or_other' => "Other"], $options);
  }

  /**
   * Tests the value callback.
   */
  public function testValueCallback() {
    $form_state = new FormState();
    $element = [
      '#cardinality' => 1,
    ];
    $input = [
      'select' => 'Selected text',
      'other' => 'Other text',
    ];

    $expected = [$input['select']];
    $values = SelectOrOtherElementBase::valueCallback($element, $input, $form_state);
    $this->assertArrayEquals($expected, $values, 'Returned single value select.');

    $input['select'] = 'select_or_other';
    $expected = [$input['other']];
    $values = SelectOrOtherElementBase::valueCallback($element, $input, $form_state);
    $this->assertArrayEquals($expected, $values, 'Returned single value other.');

    $element['#cardinality'] = -1;
    $input['select'] = ['Selected text'];
    $expected = ['select' => $input['select'], 'other' => []];
    $values = SelectOrOtherElementBase::valueCallback($element, $input, $form_state);
    $this->assertArrayEquals($expected, $values, 'Returned select array and empty other array.');

    $input['select'][] = 'select_or_other';
    $expected['other'] = [$input['other']];
    $values = SelectOrOtherElementBase::valueCallback($element, $input, $form_state);
    $this->assertArrayEquals($expected, $values, 'Returned select array and other array.');

    $input['select'] = ['select_or_other'];
    $expected = ['select' => [], 'other' => [$input['other']]];
    $values = SelectOrOtherElementBase::valueCallback($element, $input, $form_state);
    $this->assertArrayEquals($expected, $values, 'Returned empty select and other array.');

    $input['select'][] = 'Selected';
    $element['#merged_values'] = TRUE;
    $expected = ['Selected', $input['other']];
    $values = SelectOrOtherElementBase::valueCallback($element, $input, $form_state);
    $this->assertArrayEquals($expected, $values, 'Returned merged array.');

    $input['select'] = ['Selected'];
    $input['other'] = '';
    $expected = ['Selected'];
    $values = SelectOrOtherElementBase::valueCallback($element, $input, $form_state);
    $this->assertArrayEquals($expected, $values, 'Returned merged array.');

  }

  /**
   * Tests the processing of a select or other element.
   */
  public function testProcessSelectOrOther() {
    // Test SelectOrOtherElementBase.
    // Make the protected method accessible and invoke it.
    $method = new ReflectionMethod('Drupal\select_or_other\Element\SelectOrOtherElementBase', 'addOtherOption');
    $method->setAccessible(TRUE);

    $form_state = new FormState();
    $form = [];
    $original_element = $element = [
      '#title' => 'Title',
      '#title_display' => 'above',
      '#default_value' => 'default',
      '#required' => TRUE,
      '#options' => [
        'first_option' => 'First option',
        'second_option' => "Second option"
      ],
    ];

    $base_expected_element = $expected_element = $element + [
        'select' => [
          '#title' => $element['#title'],
          '#title_display' => $element['#title_display'],
          '#default_value' => $element['#default_value'],
          '#required' => $element['#required'],
          '#options' => $method->invoke(NULL, $element['#options']),
          '#weight' => 10,
        ],
        'other' => [
          '#type' => 'textfield',
          '#weight' => 20,
          '#default_value' => 'default',
        ]
      ];

    $resulting_element = SelectOrOtherElementBase::processSelectOrOther($element, $form_state, $form);
    $this->assertArrayEquals($expected_element, $resulting_element);
    $this->assertArrayEquals($resulting_element, $element);

    // Test SelectOrOtherButtons.
    $element = $original_element + ['#multiple' => TRUE];
    $expected_element = array_merge_recursive($base_expected_element, ['#multiple' => TRUE, 'select' => ['#type' => 'checkboxes']]);
    $resulting_element = SelectOrOtherButtons::processSelectOrOther($element, $form_state, $form);
    $this->assertArrayEquals($expected_element, $resulting_element);
    $this->assertArrayEquals($resulting_element, $element);

    $element = $original_element + ['#multiple' => FALSE];
    $expected_element = array_merge_recursive($base_expected_element, ['#multiple' => FALSE, 'select' => ['#type' => 'radios']]);
    $resulting_element = SelectOrOtherButtons::processSelectOrOther($element, $form_state, $form);
    $this->assertArrayEquals($expected_element, $resulting_element);
    $this->assertArrayEquals($resulting_element, $element);

    // Test SelectOrOtherSelect
    $element = $original_element;
    $expected_element = array_merge_recursive($base_expected_element, ['select' => ['#type' => 'select']]);
    $resulting_element = SelectOrOtherSelect::processSelectOrOther($element, $form_state, $form);
    $this->assertArrayEquals($expected_element, $resulting_element);
    $this->assertArrayEquals($resulting_element, $element);

  }

  /**
   * Tests the getInfo() method.
   *
   * Since this returns a hardcoded array, we'll just test if it actually does
   * return an array to get 100% coverage for this class.
   */
  public function testGetInfo() {
    /** @var SelectOrOtherElementBase $stub */
    $stub = $this->getMockForAbstractClass('Drupal\select_or_other\Element\SelectOrOtherElementBase', [], '', FALSE);
    $this->assertTrue(is_array($stub->getInfo()));
  }

}
