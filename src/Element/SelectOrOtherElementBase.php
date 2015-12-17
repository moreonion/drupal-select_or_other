<?php
/**
 * @file
 * Contains Drupal\select_or_other\Element\SelectOrOtherElementBase.
 */

namespace Drupal\select_or_other\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;

/**
 * Base class for select or other form elements.
 *
 * Properties:
 * - #cardinality: The maximum number of options that can be selected.
 * - #select_type: Either 'list' for a select list and 'buttons' for checkboxes
 *   or radio buttons depending on cardinality.
 * - #merged_values: Set this to true if the widget should return a single array
 *   with the merged values from both the 'select' and 'other' fields.
 * - #options: An associative array, where the keys are the retured values for
 *   each option, and the values are the options to be presented to the user.
 * - #empty_option: The label that will be displayed to denote no selection.
 * - #empty_value: The value of the option that is used to denote no selection.
 *
 */
abstract class SelectOrOtherElementBase extends FormElement {

  /**
   * Adds an 'other' option to the selectbox.
   */
  protected static function addOtherOption($options) {
    $options['select_or_other'] = 'Other';

    return $options;
  }

  /**
   * Prepares a form API #states array.
   * @param $state
   * @param $elementName
   * @param $valueKey
   * @param $value
   * @return array
   */
  protected static function prepareStates($state, $elementName, $valueKey, $value) {
    return [
      $state => [
        ':input[name="' . $elementName . '"]' => [$valueKey => $value],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   * @codeCoverageIgnore
   */
  public function getInfo() {
    $class = get_class($this);
    return array(
      '#input' => TRUE,
      '#process' => [
        [$class, 'processSelectOrOther'],
      ],
      '#cardinality' => 1,
      '#select_type' => 'list',
      '#merged_values' => FALSE,
      '#theme_wrappers' => ['form_element'],
      '#options' => [],
      '#tree' => TRUE,
    );
  }

  /**
   * Render API callback: Expands the select_or_other element type.
   *
   * Expands the select or other element to have a 'select' and 'other' field.
   */
  public static function processSelectOrOther(&$element, FormStateInterface $form_state, &$complete_form) {
    $element['select'] = [
      '#name' => $element['#name'],
      '#default_value' => $element['#default_value'],
      '#required' => $element['#required'],
      '#cardinality' => $element['#cardinality'],
      '#options' => SelectOrOtherElementBase::addOtherOption($element['#options']),
      '#weight' => 10,
    ];

    // Create the 'other' textfield.
    $element['other'] = [
      '#type' => 'textfield',
      '#weight' => 20,
      '#default_value' => 'default',
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    $values = [];
    if ($input !== FALSE) {

      if ($element['#cardinality'] !== 1) {
        $values = [
          'select' => (array) $input['select'],
          'other' => !empty($input['other']) ? (array) $input['other'] : [],
        ];

        if (in_array('select_or_other', $values['select'])) {
          $values['select'] = array_diff($values['select'], ['select_or_other']);
        }
        else {
          $values['other'] = [];
        }

        if (isset($element['#merged_values']) && $element['#merged_values']) {
          if (!empty($values['other'])) {
            $values = array_merge($values['select'], $values['other']);
          }
          else {
            $values = $values['select'];
          }
        }

      }
      else {
        if ($input['select'] === 'select_or_other') {
          $values = [$input['other']];
        }
        else {
          $values = [$input['select']];
        }
      }
    }

    return $values;
  }

}
