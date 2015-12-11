<?php

namespace Drupal\select_or_other\Tests;

use Drupal\Core\Form\FormState;
use Drupal\select_or_other\Element\SelectOrOtherElementBase;
use Drupal\Tests\UnitTestCase;
use ReflectionMethod;

/**
 * Tests the form element implementation.
 *
 * @group Select or Other
 */
class SelectOrOtherElementsTestCase extends UnitTestCase {

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

  }

}
