<?php

/**
 * @file
 * Contains \Drupal\select_or_other\Plugin\Field\FieldWidget\SelectOrOtherWidget.
 */

namespace Drupal\select_or_other\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\select_or_other\Plugin\Field\FieldWidget\SelectOrOtherWidgetBase;

/**
 * Plugin implementation of the 'select_or_other' widget.
 *
 * @FieldWidget(
 *   id = "select_or_other",
 *   label = @Translation("Select (or other) list"),
 *   field_types = {
 *     "text",
 *     "number_integer",
 *     "number_decimal",
 *     "number_float",
 *   }
 * )
 */
class SelectOrOtherWidget extends SelectOrOtherWidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, array &$form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $element += array(
      '#type' => 'select_or_other',
      '#options' => $this->getOptions($items[$delta]),
      '#default_value' => $items[$delta]->value,
      // Do not display a 'multiple' select box if there is only one option.
      '#multiple' => $this->multiple && count($this->options) > 1,
      '#maxlength' => $this->getFieldSetting('max_length'),
      '#other' => $this->getSetting('other'),
      '#other_title' => $this->getSetting('other_title'),
      '#other_size' => $this->getSetting('other_size'),
      '#other_delimiter' => FALSE,
      '#other_unknown_defaults' => $this->getSetting('other_unknown_defaults'),
      '#field_widget' => 'select_or_other',
      '#select_type' => 'select',
    );

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, array &$form_state) {
    $element = parent::settingsForm($form, $form_state);

    $element['available_options'] = array(
      '#type' => 'textarea',
      '#title' => t('Available options'),
      '#description' => t('A list of values that are, by default, available for selection. Enter one value per line, in the format key|label. The key is the value that will be stored in the database, and the label is what will be displayed to the user.'),
      '#default_value' => $this->getSetting('available_options'),
      '#required' => TRUE,
    );
    $element['other'] = array(
      '#type' => 'textfield',
      '#title' => t('<em>Other</em> option'),
      '#description' => t('Label for the option that the user will choose when they want to supply an <em>other</em> value.'),
      '#default_value' => $this->getSetting('other'),
      '#required' => TRUE,
    );
    $element['other_title'] = array(
      '#type' => 'textfield',
      '#title' => t('<em>Other</em> field title'),
      '#description' => t('Label for the field in which the user will supply an <em>other</em> value.'),
      '#default_value' => $this->getSetting('other_title'),
    );
    $element['other_unknown_defaults'] = array(
      '#type' => 'select',
      '#title' => t('<em>Other</em> value as default value'),
      '#description' => t("If any incoming default values do not appear in <em>available options</em> (i.e. set as <em>other</em> values), what should happen?"),
      '#options' => array(
        'other' => t('Add the values to the other textfield'),
        'append' => t('Append the values to the current list'),
        'available' => t('Append the values to the available options'),
        'ignore' => t('Ignore the values'),
      ),
      '#default_value' => $this->getSetting('other_unknown_defaults'),
      '#required' => TRUE,
    );
    $element['other_size'] = array(
      '#type' => 'number',
      '#title' => t('<em>Other</em> field size'),
      '#default_value' => $this->getSetting('other_size'),
      '#required' => TRUE,
    );
    $element['sort_options'] = array(
      '#type' => 'checkbox',
      '#title' => t('Sort options'),
      '#description' => t("Sorts the options in the list alphabetically by value."),
      '#default_value' => $this->getSetting('sort_options'),
    );

    return $element;
  }

   /**
   * {@inheritdoc}
   */
  static protected function sanitizeLabel(&$label) {
    // Select form inputs allow unencoded HTML entities, but no HTML tags.
    $label = strip_tags($label);
  }

  /**
   * {@inheritdoc}
   */
  protected function supportsGroups() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEmptyOption() {
    if ($this->multiple) {
      // Multiple select: add a 'none' option for non-required fields.
      if (!$this->required) {
        return static::SELECT_OR_OTHER_EMPTY_NONE;
      }
    }
    else {
      // Single select: add a 'none' option for non-required fields,
      // and a 'select a value' option for required fields that do not come
      // with a value selected.
      if (!$this->required) {
        return static::SELECT_OR_OTHER_EMPTY_NONE;
      }
      if (!$this->has_value) {
        return static::SELECT_OR_OTHER_EMPTY_SELECT;
      }
    }
  }

}
