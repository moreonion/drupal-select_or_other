<?php
/**
 * @file
 * Contains Drupal\select_or_other\Element\SelectOrOtherSelect.
 */

namespace Drupal\select_or_other\Element;


use Drupal\Core\Form\FormStateInterface;

/**
 *  * Provides a form element with a select box and other option.
 *
 * Properties:
 * @see SelectOrOtherElementBase
 *
 * @FormElement("select_or_other_select")
 */
class SelectOrOtherSelect extends SelectOrOtherElementBase {

  /**
   * {@inheritdoc}
   */
  public static function processSelectOrOther(&$element, FormStateInterface $form_state, &$complete_form) {
    $element = parent::processSelectOrOther($element, $form_state, $complete_form);

    $element['select']['#type'] = 'select';

    return $element;
  }

}
