<?php

/**
 * @file
 * Contains \Drupal\select_or_other\Plugin\Field\FieldFormatter\SelectOrOtherFormatter.
 */

namespace Drupal\email\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'select_or_other' formatter.
 *
 * @FieldFormatter(
 *   id = "select_or_other_formatter",
 *   label = @Translation("Select or other"),
 *   field_types = {
 *     "text",
 *     "number_integer",
 *     "number_decimal",
 *     "number_float"
 *   }
 * )
 */
class SelectOrOtherFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items) {
    $element       = array();
    $field_options = array();
    $settings      = $display['settings'];

    if ($this->getSetting('available_options')) {
      $field_options = explode("\n", $this->getSetting('available_options'));
      $pos = strpos($this->getSetting('available_options'), '|');

      if ($pos !== FALSE) {
        // There are keys.
        foreach ($field_options as $field_item) {
          $exploded = explode('|', $field_item);
          $temp_options[$exploded[0]] = $exploded[1];
        }
        $field_options = $temp_options;
      }
    }

    foreach ($items as $delta => $item) {
      if (array_key_exists($item['value'], $field_options)) {
        $element[$delta] = array('#markup' => $field_options[$item['value']]);
      }
      else {
        $element[$delta] = array('#markup' => $item['value']);
      }
    }

    return $elements;
  }

}
