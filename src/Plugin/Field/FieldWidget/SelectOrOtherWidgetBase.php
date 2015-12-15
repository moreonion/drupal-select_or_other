<?php

/**
 * @file
 * Contains \Drupal\select_or_other\Plugin\Field\FieldWidget\SelectOrOtherWidgetBase.
 */

namespace Drupal\select_or_other\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Base class for the 'select_or_other_*' widgets.
 *
 * Field types willing to enable one or several of the widgets defined in
 * select_or_other.module (select, radios/checkboxes, on/off checkbox) need to
 * implement the AllowedValuesInterface to specify the list of options to
 * display in the widgets.
 *
 * @see \Drupal\Core\TypedData\AllowedValuesInterface
 */
abstract class SelectOrOtherWidgetBase extends WidgetBase {

  /**
   * Identifies a 'None' option.
   */
  const SELECT_OR_OTHER_EMPTY_NONE = 'options_none';

  /**
   * Identifies a 'Select a value' option.
   */
  const SELECT_OR_OTHER_EMPTY_SELECT = 'options_select';

  /**
   * @var string
   */
  protected $multiple;

  /**
   * @var string
   */
  protected $required;

  /**
   * @var string
   */
  protected $options;

  /**
   * @var string
   */
  private $has_value;

  /**
   * Helper method to determine the identifying column for the field.
   *
   * @return string
   *   The name of the column.
   */
  protected function getColumn() {
    static $property_names;

    if (empty($property_names)) {
      $property_names = $this->fieldDefinition->getFieldStorageDefinition()
        ->getPropertyNames();
    }

    return reset($property_names);
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'select_element_type' => 'select_or_other_select',
      'available_options' => '',
      'other' => 'Other',
      'other_title' => '',
      'other_unknown_defaults' => 'other',
      'other_size' => 60,
      'sort_options' => 0,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    $form['select_element_type'] = [
      '#title' => t('Type of select form element'),
      '#type' => 'select',
      '#options' => $this->selectElementTypeOptions(),
      '#default_value' => $this->getSetting('select_element_type'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    $options = $this->selectElementTypeOptions();
    $summary[] = t('Type of select form element') . ': ' . $options[$this->getSetting('select_element_type')];

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    // Prepare some properties for the child methods to build the actual form
    // element.
    $this->required = $element['#required'];
    $this->multiple = $this->fieldDefinition->getFieldStorageDefinition()
      ->isMultiple();
    $this->has_value = isset($items[0]->{$this->getColumn()});


    // Add our custom validator.
    $element['#element_validate'][] = array(
      get_class($this),
      'validateElement'
    );
    $element['#key_column'] = $this->getColumn();

    // The rest of the $element is built by child method implementations.

    return $element;
  }

  /**
   *
   * Return whether $items of formElement method contains any data.
   *
   * @return bool
   */
  public function hasValue() {
    return $this->has_value;
  }

  /**
   * Form validation handler for widget elements.
   *
   * @param array $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function validateElement(array $element, FormStateInterface $form_state) {
    if ($element['#required'] && $element['#value'] == '_none') {
      $form_state->setError($element, t('@name field is required.', array('@name' => $element['#title'])));
    }

    // Massage submitted form values.
    // Drupal\Core\Field\WidgetBase::submit() expects values as
    // an array of values keyed by delta first, then by column, while our
    // widgets return the opposite.

    if (is_array($element['#value'])) {
      $values = array_values($element['#value']);
    }
    else {
      $values = array($element['#value']);
    }

    // Filter out the 'none' option. Use a strict comparison, because
    // 0 == 'any string'.
    $index = array_search('_none', $values, TRUE);
    if ($index !== FALSE) {
      unset($values[$index]);
    }

    // Transpose selections from field => delta to delta => field.
    $items = array();
    foreach ($values as $value) {
      $items[] = array($element['#key_column'] => $value);
    }
    $form_state->setValueForElement($element, $items);
  }

  /**
   * Returns the array of options for the widget.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   The field item.
   *
   * @return array
   *   The array of options for the widget.
   */
  protected function getOptions(FieldItemInterface $item) {
    if (!isset($this->options)) {
      $string_options = $this->getSetting('available_options');

      $string_options = trim($string_options);
      if (empty($string_options)) {
        return [];
      }
      // If option has a key specified
      if (strpos($string_options, '|') !== FALSE) {
        $options = [];
        $list = explode("\n", $string_options);
        $list = array_map('trim', $list);
        $list = array_filter($list, 'strlen');

        foreach ($list as $position => $text) {
          $value = $key = FALSE;

          // Check for an explicit key.
          $matches = array();
          if (preg_match('/(.*)\|(.*)/', $text, $matches)) {
            $key = $matches[1];
            $value = $matches[2];
          }

          $options[$key] = (isset($value) && $value !== '') ? html_entity_decode($value) : $key;
        }
      }
      else {
        $options[$string_options] = html_entity_decode($string_options);
      }


      $label = t('N/A');

      // Add an empty option if the widget needs one.
      if ($empty_option = $this->getEmptyOption()) {
        switch ($this->getPluginId()) {
          case 'select_or_other_buttons':
            $label = t('N/A');
            break;

          case 'select_or_other':
          case 'select_or_other_sort':
            $label = ($empty_option == static::SELECT_OR_OTHER_EMPTY_NONE ? t('- None -') : t('- Select a value -'));
            break;
        }

        $options = array('_none' => $label) + $options;
      }

      array_walk_recursive($options, array($this, 'sanitizeLabel'));

      // Options might be nested ("optgroups"). If the widget does not support
      // nested options, flatten the list.
      if (!$this->supportsGroups()) {
        $options = $this->flattenOptions($options);
      }

      $this->options = $options;
    }
    return $this->options;
  }

  /**
   * Determines selected options from the incoming field values.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   The field values.
   * @param int $delta
   *   (optional) The delta of the item to get options for. Defaults to 0.
   *
   * @return array
   *   The array of corresponding selected options.
   */
  protected function getSelectedOptions(FieldItemListInterface $items, $delta = 0) {
    // We need to check against a flat list of options.
    $flat_options = $this->flattenOptions($this->getOptions($items[$delta]));

    $selected_options = array();
    foreach ($items as $item) {
      $value = $item->{$this->getColumn()};
      // Keep the value if it actually is in the list of options (needs to be
      // checked against the flat list).
      if (isset($flat_options[$value])) {
        $selected_options[] = $value;
      }
    }

    return $selected_options;
  }

  /**
   * Flattens an array of allowed values.
   *
   * @param array $array
   *   A single or multidimensional array.
   *
   * @return array
   *   The flattened array.
   */
  protected function flattenOptions(array $array) {
    $result = array();
    array_walk_recursive($array, function ($a, $b) use (&$result) {
      $result[$b] = $a;
    });
    return $result;
  }

  /**
   * Indicates whether the widgets support optgroups.
   *
   * @return bool
   *   TRUE if the widget supports optgroups, FALSE otherwise.
   */
  protected function supportsGroups() {
    return FALSE;
  }

  /**
   * Sanitizes a string label to display as an option.
   *
   * @param string $label
   *   The label to sanitize.
   */
  static protected function sanitizeLabel(&$label) {
    // Allow a limited set of HTML tags.
    $label = Xss::filter($label);
  }

  /**
   * Returns the empty option to add to the list of options, if any.
   *
   * @return string|null
   *   Either static::OPTIONS_EMPTY_NONE, static::OPTIONS_EMPTY_SELECT, or NULL.
   */
  protected function getEmptyOption() {
  }

  private function selectElementTypeOptions() {
    return [
      'select_or_other_select' => t('Select list'),
      'select_or_other_buttons' => t('Radiobuttons/checkboxes'),
    ];
  }

}
