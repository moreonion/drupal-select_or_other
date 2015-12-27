<?php
/**
 * @file
 * Contains Drupal\select_or_other\Element\Select.
 */

namespace Drupal\select_or_other\Element;


use Drupal\Core\Form\FormStateInterface;

/**
 *  * Provides a form element with a select box and other option.
 *
 * Properties:
 * @see ElementBase
 *
 * @FormElement("select_or_other_select")
 */
class Select extends ElementBase {

  /**
   * {@inheritdoc}
   */
  public static function processSelectOrOther(&$element, FormStateInterface $form_state, &$complete_form) {
    $element = parent::processSelectOrOther($element, $form_state, $complete_form);

    self::setSelectType($element);
    self::addEmptyOption($element);
    self::addStatesHandling($element);

    return $element;
  }

  /**
   * Sets the type of buttons to use for the select element.
   *
   * @param $element
   *   The select or other element.
   */
  protected static function setSelectType(&$element) {
    $element['select']['#type'] = 'select';
  }

  /**
   * Adds an empty option to the select element if required.
   *
   * @param $element
   */
  protected static function addEmptyOption(&$element) {
    if (!isset($element['#no_empty_option']) || !$element['#no_empty_option']) {
      if (!$element['#required'] || empty($element['#default_value'])) {
        $element['select']['#empty_value'] = '';
      }
    }
  }

  /**
   * Adds a #states array to the other field to make hide/show work.
   *
   * @param $element
   *   The select or other element.
   */
  protected static function addStatesHandling(&$element) {
    if (!$element['#multiple']) {
      $element['other']['#states'] = self::prepareState('visible', $element['#name'] . '[select]', 'value', 'select_or_other');
    }
    else {
      $element['select']['#multiple'] = TRUE;

      // todo Drupal #states does not support multiple select elements. We have
      // to simulate #states using our own javascript until #1149078 is
      // resolved. @see https://www.drupal.org/node/1149078
      $element['select']['#attached'] = [
        'library' => ['select_or_other/multiple_select_states_hack']
      ];
    }
  }

}
