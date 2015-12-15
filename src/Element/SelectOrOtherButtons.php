<?php
/**
 * @file
 * Contains Drupal\select_or_other\Element\SelectOrOtherButtons.
 */

namespace Drupal\select_or_other\Element;


use Drupal\Core\Form\FormStateInterface;


/**
 * Provides a form element with buttons and other option.
 *
 * Properties:
 * @see SelectOrOtherElementBase
 *
 * @FormElement("select_or_other_buttons")
 */
class SelectOrOtherButtons extends SelectOrOtherElementBase {

  /**
   * {@inheritdoc}
   */
  public static function processSelectOrOther(&$element, FormStateInterface $form_state, &$complete_form) {
    $element = parent::processSelectOrOther($element, $form_state, $complete_form);

    if ($element['#multiple']) {
      $element['select']['#type'] = 'checkboxes';
    }
    else {
      $element['select']['#type'] = 'radios';
    }

    return $element;
  }

}