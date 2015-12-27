<?php
/**
 * @file
 *  Contains \Drupal\select_or_other\Tests\TestBase.
 */

namespace Drupal\select_or_other\Tests;

use Drupal\simpletest\WebTestBase;

/**
 *  Base test class for select or other widgets.
 *
 * @codeCoverageIgnore
 *   Our unittests do not have to cover the integration tests.
 */
abstract class TestBase extends WebTestBase {

  /**
   * @var array
   *   The default permissions to give to a newly created user.
   */
  protected $defaultPermissions;

  /**
   * @var array $fields
   *   associated array keyed by field_name with the following information:
   *   - Widget (machine name of the widget used)
   *   - Cardinality (1, -1)
   *   - field_settings @see field_info_field().
   *   - instance_settings @see field_info_instance().
   *
   */
  protected $fields;

  /**
   * @var array
   *   The module(s) to enable when running these tests.
   */
  public static $modules = ['block', 'select_or_other'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->drupalPlaceBlock('local_tasks_block');
  }

  /**
   * Make sure an empty option is present when relevant.
   */
  public function testEmptyOption($other_option = '') {
    foreach ($this->fields as $field_name => $field) {
      $this->drupalGet('node/add/' . $this->getFieldContentType($field_name));
      $select_type = $field['select_type'];
      $multiple = $field['cardinality'] !== 1;
      $required = $field['required'];

      // First test empty behaviour. Only single select boxes should always have
      // an empty value.
      if ($select_type === 'select_or_other_select' && !$multiple) {
        if ($required) {
          $this->assertText(t('- Select -'));
        }
        else {
          $this->assertText(t('- None -'));
        }
      }
      else {
        $this->assertNoText(t('- Select -'));
        $this->assertNoText(t('- None -'));
      }

      // Test non-empty behaviour. Once again only single cardinality elements
      // should have empty options to allow for the removal of values if the
      // field is not required.
      if ($other_option !== '') {
        // We set the other option, because we can set that in the same way
        // always.
        $this->setFieldValue($field_name, 'select_or_other', $other_option);
        $this->clickLink(t('Edit'));
        if (!$multiple && !$required) {
          $this->assertText(t('- None -'));
        }
        else {
          $this->assertNoText(t('- Select -'));
          $this->assertNoText(t('- None -'));
        }
      }
      else {
        $this->fail('No $other_option provided, this test should be overridden and calling itself from individual test classes.');
      }
    }
  }

  /**
   * Generate content types and fields for testing.
   * @param string $field_type
   *   The type of field to create.
   * @param array $field_settings
   * @param string $widget
   * @param array $select_types
   * @param array $widget_settings
   *   The widget settings.
   * @param $field_formatter
   *   Machine name of the field formatter to use for display.
   */
  protected function prepareTestFields($field_type, array $field_settings, $widget, array $select_types, $widget_settings, $field_formatter) {
    // Configure fields.
    foreach ($select_types as $select_type) {
      foreach (array(1, -1) as $cardinality) {
        foreach (array(TRUE, FALSE) as $required) {
          $bundle = $this->drupalCreateContentType()->id();
          $field_name = strtolower($this->randomMachineName());
          $vocabulary = $this->randomMachineName();
          $this->fields[$field_name] = [
            'bundle' => $bundle,
            'widget' => $widget,
            'select_type' => $select_type,
            'cardinality' => $cardinality,
            'required' => $required,
            'vocabulary' => strtolower($vocabulary),
          ];

          // Create a vocabulary.
          \Drupal::entityTypeManager()
            ->getStorage('taxonomy_vocabulary')
            ->create(['vid' => strtolower($vocabulary), 'name' => $vocabulary])
            ->save();

          // Create the field
          $field_defaults = [
            'field_name' => $field_name,
            'entity_type' => 'node',
            'bundle' => $bundle,
          ];
          $field_info = $field_defaults + [
              'type' => $field_type,
              'settings' => $field_settings,
              'cardinality' => $cardinality,
            ];
          \Drupal::entityTypeManager()
            ->getStorage('field_storage_config')
            ->create($field_info)
            ->save();

          // Create the instance
          $instance_info = $field_defaults + [
              'field_type' => $field_type,
              'required' => $required,
              'label' => $field_name,
              'settings' => [
                'handler_settings' => [
                  'target_bundles' => [
                    $vocabulary => $vocabulary,
                  ],
                  'auto_create' => TRUE,
                ],
              ],
            ];
          $instance = \Drupal::entityTypeManager()
            ->getStorage('field_config')
            ->create($instance_info);
          $instance->save();


          entity_get_form_display('node', $bundle, 'default')
            ->setComponent($field_name, [
              'type' => $widget,
              'settings' => [
                'select_element_type' => $select_type,
              ],
            ])
            ->save();
        }
      }
    }

    // Determine required permissions.
    $this->defaultPermissions = ['bypass node access'];
  }

  /**
   * Submit the add node form with the selected values.
   * @param $field_name
   * @param $select
   * @param $other
   */
  protected function setFieldValue($field_name, $select, $other = '') {
    $edit = array();

    // A node requires a title.
    $edit["title[0][value]"] = $this->randomMachineName(8);

    $this->drupalGet('node/add/' . $this->getFieldContentType($field_name));

    // Set the select value.
    if ($this->fields[$field_name]['cardinality'] == 1) {
      $edit["{$field_name}[select]"] = $select;
    }
    else {
      // We have to treat multiple selection elements differently.
      if ($this->fields[$field_name]['select_type'] === 'select_or_other_select') {
        // We're dealing with a multiple select.
        $edit["{$field_name}[select][]"] = $select;
      }
      else {
        // We're dealing with checkboxes.
        $edit["{$field_name}[select][{$select}]"] = $select;
      }
    }

    // Set the other value.
    $edit["{$field_name}[other]"] = $other;

    // Create the node.
    $this->drupalPostForm(NULL, $edit, t('Save'));
  }

  /**
   * Returns the machine name of the content type a field is configured on.
   *
   * @param $field_name
   *   The field machine name of which to retrieve the content type.
   * @return string|null
   *   The content type machine name or NULL if not found.
   */
  protected function getFieldContentType($field_name) {
    return $this->fields[$field_name]['bundle'];
  }

}
