<?php

/**
 * @package toolkit
 */

/**
 * A simple Radio button field that essentially maps to HTML's `<input type="radio" />`. The
 * options for this field can be static, or feed from another field.
 * The logic is copied from the select field.
 */
class FieldRadio extends FieldTagList implements ExportableField, ImportableField
{
    public function __construct()
    {
        parent::__construct();
        $this->_name = __('Radio Button');
        $this->_required = true;
        $this->_showassociation = true;

        // Set default
        $this->set('show_column', 'yes');
        $this->set('location', 'sidebar');
        $this->set('required', 'no');
    }

/*-------------------------------------------------------------------------
    Definition:
-------------------------------------------------------------------------*/

    public function canToggle()
    {
        return ($this->get('allow_multiple_selection') === 'yes' ? false : true);
    }

    public function getToggleStates()
    {
        $values = preg_split('/,\s*/i', $this->get('static_options'), -1, PREG_SPLIT_NO_EMPTY);

        if ($this->get('dynamic_options') != '') {
            $this->findAndAddDynamicOptions($values);
        }

        $values = array_map('trim', $values);
        // Fixes issues on PHP5.3. RE: #1773 ^BA
        if (empty($values)) {
            return $values;
        }

        $states = array_combine($values, $values);

        if ($this->get('sort_options') === 'yes') {
            natsort($states);
        }

        return $states;
    }

    public function toggleFieldData(array $data, $newState, $entry_id = null)
    {
        $data['value'] = $newState;
        $data['handle'] = Lang::createHandle($newState);

        return $data;
    }

    public function canFilter()
    {
        return true;
    }

    public function canPrePopulate()
    {
        return true;
    }

    public function isSortable()
    {
        return true;
    }

    public function allowDatasourceOutputGrouping()
    {
        // Grouping follows the same rule as toggling.
        return $this->canToggle();
    }

    public function allowDatasourceParamOutput()
    {
        return true;
    }

    public function requiresSQLGrouping()
    {
        // SQL grouping follows the opposite rule as toggling.
        return !$this->canToggle();
    }

    public function fetchSuggestionTypes()
    {
        return array('association', 'static');
    }

    /*-------------------------------------------------------------------------
        Setup:
    -------------------------------------------------------------------------*/

    public function createTable()
    {
        return Symphony::Database()->query(
            "CREATE TABLE IF NOT EXISTS `tbl_entries_data_" . $this->get('id') . "` (
              `id` int(11) unsigned NOT null auto_increment,
              `entry_id` int(11) unsigned NOT null,
              `handle` varchar(200) default null,
              `value` varchar(200) default null,
              PRIMARY KEY  (`id`),
              KEY `entry_id` (`entry_id`),
              KEY `handle` (`handle`),
              KEY `value` (`value`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;"
        );
    }

    /*-------------------------------------------------------------------------
        Utilities:
    -------------------------------------------------------------------------*/

    public function findAndAddDynamicOptions(&$values)
    {
        if (!is_array($values)) {
            $values = array();
        }

        $results = false;

        // Fixes #1802
        if (!Symphony::Database()->tableExists('tbl_entries_data_' . $this->get('dynamic_options'))) {
            return;
        }

        // Ensure that the table has a 'value' column
        if ((boolean)Symphony::Database()->fetchVar('Field', 0, sprintf(
            "SHOW COLUMNS FROM `tbl_entries_data_%d` LIKE '%s'",
            $this->get('dynamic_options'),
            'value'
        ))) {
            $results = Symphony::Database()->fetchCol('value', sprintf(
                "SELECT DISTINCT `value`
                FROM `tbl_entries_data_%d`
                ORDER BY `value` ASC",
                $this->get('dynamic_options')
            ));
        }

        // In the case of a Upload field, use 'file' instead of 'value'
        if (($results == false) && (boolean)Symphony::Database()->fetchVar('Field', 0, sprintf(
            "SHOW COLUMNS FROM `tbl_entries_data_%d` LIKE '%s'",
            $this->get('dynamic_options'),
            'file'
        ))) {
            $results = Symphony::Database()->fetchCol('file', sprintf(
                "SELECT DISTINCT `file`
                FROM `tbl_entries_data_%d`
                ORDER BY `file` ASC",
                $this->get('dynamic_options')
            ));
        }

        if ($results) {
            if ($this->get('sort_options') == 'no') {
                natsort($results);
            }

            $values = array_merge($values, $results);
        }
    }

    /*-------------------------------------------------------------------------
        Settings:
    -------------------------------------------------------------------------*/

    public function findDefaults(array &$settings)
    {
        if (!isset($settings['display_inline'])) {
            $settings['display_inline'] = 'no';
        }

        if (!isset($settings['show_association'])) {
            $settings['show_association'] = 'no';
        }

        if (!isset($settings['sort_options'])) {
            $settings['sort_options'] = 'no';
        }
    }

    public function displaySettingsPanel(XMLElement &$wrapper, $errors = null)
    {
        Field::displaySettingsPanel($wrapper, $errors);

        $div = new XMLElement('div', null, array('class' => 'two columns'));

        // Predefined Values
        $label = Widget::Label(__('Static Values'));
        $label->setAttribute('class', 'column');
        $label->appendChild(new XMLElement('i', __('Optional')));
        $input = Widget::Input('fields['.$this->get('sortorder').'][static_options]', General::sanitize($this->get('static_options')));
        $label->appendChild($input);
        $div->appendChild($label);

        // Dynamic Values
        // Only append selected ids, load full section information asynchronously
        $label = Widget::Label(__('Dynamic Values'));
        $label->setAttribute('class', 'column');
        $label->appendChild(new XMLElement('i', __('Optional')));

        $options = array(
            array('', false, __('None'))
        );

        if ($this->get('dynamic_options')) {
            $options[] = array($this->get('dynamic_options'));
        }

        $label->appendChild(
            Widget::Select('fields['.$this->get('sortorder').'][dynamic_options]', $options, array(
                'class' => 'js-fetch-sections'
            ))
        );

        if (isset($errors['dynamic_options'])) {
            $div->appendChild(Widget::Error($label, $errors['dynamic_options']));
        } else {
            $div->appendChild($label);
        }

        $wrapper->appendChild($div);

        // Other settings
        $div = new XMLElement('div', null, array('class' => 'two columns'));

        // Sort options?
        $this->createCheckboxSetting($div, 'sort_options', __('Sort all options alphabetically'));

        // Show radio buttons horizontally
        $this->createCheckboxSetting($div, 'display_inline', __('Show radio buttons horizontally'));

        $wrapper->appendChild($div);

        // Associations
        $fieldset = new XMLElement('fieldset');
        $this->appendAssociationInterfaceSelect($fieldset);
        $this->appendShowAssociationCheckbox($fieldset);
        $wrapper->appendChild($fieldset);

        // Requirements and table display
        $this->appendStatusFooter($wrapper);
    }

    public function checkFields(array &$errors, $checkForDuplicates = true)
    {
        if (!is_array($errors)) {
            $errors = array();
        }

        if ($this->get('static_options') == '' && ($this->get('dynamic_options') == '' || $this->get('dynamic_options') == 'none')) {
            $errors['dynamic_options'] = __('At least one source must be specified, dynamic or static.');
        }

        Field::checkFields($errors, $checkForDuplicates);
    }

    public function commit()
    {
        if (!Field::commit()) {
            return false;
        }

        $id = $this->get('id');

        if ($id === false) {
            return false;
        }

        $fields = array();

        if ($this->get('static_options') != '') {
            $fields['static_options'] = $this->get('static_options');
        }

        if ($this->get('dynamic_options') != '') {
            $fields['dynamic_options'] = $this->get('dynamic_options');
        }

        $fields['display_inline'] = ($this->get('display_inline') ? $this->get('display_inline') : 'no');
        $fields['sort_options'] = $this->get('sort_options') === 'yes' ? 'yes' : 'no';

        if (!FieldManager::saveSettings($id, $fields)) {
            return false;
        }

        SectionManager::removeSectionAssociation($id);

        // Dynamic Options isn't an array like in Select Box Link
        $field_id = $this->get('dynamic_options');

        if (!is_null($field_id) && is_numeric($field_id)) {
            SectionManager::createSectionAssociation(null, $id, (int)$field_id, $this->get('show_association') === 'yes' ? true : false, $this->get('association_ui'), $this->get('association_editor'));
        }

        return true;
    }

    /*-------------------------------------------------------------------------
        Publish:
    -------------------------------------------------------------------------*/

    public function displayPublishPanel(XMLElement &$wrapper, $data = null, $flagWithError = null, $fieldnamePrefix = null, $fieldnamePostfix = null, $entry_id = null)
    {
        $states = $this->getToggleStates();
        $value = isset($data['value']) ? $data['value'] : null;

        if (!is_array($value)) {
            $value = array($value);
        }

        $options = array();

        foreach ($states as $handle => $v) {
            $options[] = array(General::sanitize($v), in_array($v, $value), General::sanitize($v));
        }

        $fieldname = 'fields'.$fieldnamePrefix.'['.$this->get('element_name').']'.$fieldnamePostfix;

        $fieldset = new XMLElement('fieldset');
        $legend = new XMLElement('legend', $this->get('label'));
        $fieldset->appendChild($legend);

        if ($this->get('required') !== 'yes') {
            $legend->appendChild(new XMLElement('i', __('Optional')));
        }

        $i = 0;
        // Create the radio buttons
        if ($this->get('display_inline') === 'yes') {
            foreach ($options as $opt) {
                $i++;
                $radio = Widget::input($fieldname, $opt[0], 'radio'); // Value
                $radio->setAttribute('id', $this->get('element_name') . 'Choice' . $i);
                if ($this->get('required') === 'yes') {
                    $radio->setAttribute('required', 'required');
                }
                if ($opt[1] === true) {
                    $radio->setAttribute('checked', 'checked');
                }

                $label = new XMLElement('label', $opt[2]); // Display name
                $label->setAttribute('for', $this->get('element_name') . 'Choice' . $i);
                $label->setAttribute('class', 'label-inline');

                $fieldset->appendChild($radio);
                $fieldset->appendChild($label);
            }

        } else {
            foreach ($options as $opt) {
                $label = new XMLElement('label');
                $radio = Widget::input($fieldname, $opt[0], 'radio'); // Value
                if ($this->get('required') === 'yes') {
                    $radio->setAttribute('required', 'required');
                }
                if ($opt[1] === true) {
                    $radio->setAttribute('checked', 'checked');
                }

                $label->appendChild($radio);

                $span = new XMLElement('span', $opt[2]);
                $label->appendChild($span);

                $fieldset->appendChild($label);
            }
        }

        if ($flagWithError != null) {
            $wrapper->appendChild(Widget::Error($fieldset, $flagWithError));
        } else {
            $wrapper->appendChild($fieldset);
        }
    }

    public function checkPostFieldData($data, &$message, $entry_id = null)
    {
        return Field::checkPostFieldData($data, $message, $entry_id);
    }

    public function processRawFieldData($data, &$status, &$message = null, $simulate = false, $entry_id = null)
    {
        $status = self::__OK__;

        if (!is_array($data)) {
            return array(
                'value' => $data,
                'handle' => Lang::createHandle($data)
            );
        }

        if (empty($data)) {
            return null;
        }

        $result = array(
            'value' => array(),
            'handle' => array()
        );

        foreach ($data as $value) {
            $result['value'][] = $value;
            $result['handle'][] = Lang::createHandle($value);
        }

        return $result;
    }

    /*-------------------------------------------------------------------------
        Output:
    -------------------------------------------------------------------------*/

    public function prepareTextValue($data, $entry_id = null)
    {
        $value = $this->prepareExportValue($data, ExportableField::LIST_OF + ExportableField::VALUE, $entry_id);

        return implode(', ', $value);
    }

    /*-------------------------------------------------------------------------
        Import:
    -------------------------------------------------------------------------*/

    public function prepareImportValue($data, $mode, $entry_id = null)
    {
        $message = $status = null;
        $modes = (object)$this->getImportModes();

        if (!is_array($data)) {
            $data = array($data);
        }

        if ($mode === $modes->getValue) {
            if ($this->get('display_inline') === 'no') {
                $data = array(implode('', $data));
            }

            return $data;
        } elseif ($mode === $modes->getPostdata) {
            return $this->processRawFieldData($data, $status, $message, true, $entry_id);
        }

        return null;
    }

/*-------------------------------------------------------------------------
    Export:
-------------------------------------------------------------------------*/

    /**
     * Return a list of supported export modes for use with `prepareExportValue`.
     *
     * @return array
     */
    public function getExportModes()
    {
        return array(
            'listHandle' =>         ExportableField::LIST_OF
                                    + ExportableField::HANDLE,
            'listValue' =>          ExportableField::LIST_OF
                                    + ExportableField::VALUE,
            'listHandleToValue' =>  ExportableField::LIST_OF
                                    + ExportableField::HANDLE
                                    + ExportableField::VALUE,
            'getPostdata' =>        ExportableField::POSTDATA
        );
    }

    /**
     * Give the field some data and ask it to return a value using one of many
     * possible modes.
     *
     * @param mixed $data
     * @param integer $mode
     * @param integer $entry_id
     * @return array
     */
    public function prepareExportValue($data, $mode, $entry_id = null)
    {
        $modes = (object)$this->getExportModes();

        if (isset($data['handle']) && is_array($data['handle']) === false) {
            $data['handle'] = array(
                $data['handle']
            );
        }

        if (isset($data['value']) && is_array($data['value']) === false) {
            $data['value'] = array(
                $data['value']
            );
        }

        // Handle => Value pairs:
        if ($mode === $modes->listHandleToValue) {
            return isset($data['handle'], $data['value'])
                ? array_combine($data['handle'], $data['value'])
                : array();

            // Array of handles:
        } elseif ($mode === $modes->listHandle) {
            return isset($data['handle'])
                ? $data['handle']
                : array();

            // Array of values:
        } elseif ($mode === $modes->listValue || $mode === $modes->getPostdata) {
            return isset($data['value'])
                ? $data['value']
                : array();
        }
    }

    /*-------------------------------------------------------------------------
        Filtering:
    -------------------------------------------------------------------------*/

    public function displayFilteringOptions(XMLElement &$wrapper)
    {
        $existing_options = $this->getToggleStates();

        if (is_array($existing_options) && !empty($existing_options)) {
            $optionlist = new XMLElement('ul');
            $optionlist->setAttribute('class', 'tags');
            $optionlist->setAttribute('data-interactive', 'data-interactive');

            foreach ($existing_options as $option) {
                $optionlist->appendChild(
                    new XMLElement('li', General::sanitize($option))
                );
            };

            $wrapper->appendChild($optionlist);
        }
    }

    /*-------------------------------------------------------------------------
        Grouping:
    -------------------------------------------------------------------------*/

    public function groupRecords($records)
    {
        if (!is_array($records) || empty($records)) {
            return;
        }

        $groups = array($this->get('element_name') => array());

        foreach ($records as $r) {
            $data = $r->getData($this->get('id'));

            if (!isset($data['value'])) continue;

            if (is_array($data['value'])) {
                foreach ($data['value'] as $key => $v) {
                    $value = General::sanitize($v);

                    if (!isset($groups[$this->get('element_name')][$data['handle'][$key]])) {
                        $groups[$this->get('element_name')][$data['handle'][$key]] = array(
                            'attr' => array('handle' => $data['handle'][$key], 'value' => $value),
                            'records' => array(),
                            'groups' => array()
                        );
                    }

                    $groups[$this->get('element_name')][$data['handle'][$key]]['records'][] = $r;
                }
            }
            else {
                $value = General::sanitize($data['value']);

                if (!isset($groups[$this->get('element_name')][$data['handle']])) {
                    $groups[$this->get('element_name')][$data['handle']] = array(
                        'attr' => array('handle' => $data['handle'], 'value' => $value),
                        'records' => array(),
                        'groups' => array()
                    );
                }

                $groups[$this->get('element_name')][$data['handle']]['records'][] = $r;
            }
        }

        return $groups;
    }

    /*-------------------------------------------------------------------------
        Events:
    -------------------------------------------------------------------------*/

    public function getExampleFormMarkup()
    {
        $states = $this->getToggleStates();

        $options = array();

        foreach ($states as $handle => $v) {
            $options[] = array($v, null, $v);
        }

        $div = new XMLElement('div', null, array('class' => 'form-field'));

        $fieldname = 'fields['.$this->get('element_name').']';

        $fieldset = new XMLElement('fieldset');
        if ($this->get('display_inline') === 'yes') {
            $fieldset->setValue("\n" . __('<!-- Displayed horizontally -->'));
        } else {
            $fieldset->setValue("\n" . __('<!-- Displayed vertically -->'));
        }
        $legend = new XMLElement('legend', $this->get('label'));
        $fieldset->appendChild($legend);

        if ($this->get('required') === 'yes') {
            $mark = new XMLElement('span', '*');
            $mark->setAttribute('aria-hidden', 'true');
            $mark->setAttribute('class', 'required-mark');
            $legend->appendChild($mark);
        }

        $i = 0;
        // Create the radio buttons
        if ($this->get('display_inline') === 'yes') {
            foreach ($options as $opt) {
                $i++;
                $radio = Widget::input($fieldname, $opt[0], 'radio'); // Value
                $radio->setAttribute('id', $this->get('element_name') . 'Choice' . $i);
                if ($this->get('required') === 'yes') {
                    $radio->setAttribute('required', 'required');
                }
                if ($opt[1] === true) {
                    $radio->setAttribute('checked', 'checked');
                }

                $label = new XMLElement('label', $opt[2]); // Display name
                $label->setAttribute('for', $this->get('element_name') . 'Choice' . $i);
                $label->setAttribute('class', 'label-inline');

                $fieldset->appendChild($radio);
                $fieldset->appendChild($label);
            }

        } else {
            foreach ($options as $opt) {
                $label = new XMLElement('label');
                $radio = Widget::input($fieldname, $opt[0], 'radio'); // Value
                if ($this->get('required') === 'yes') {
                    $radio->setAttribute('required', 'required');
                }
                if ($opt[1] === true) {
                    $radio->setAttribute('checked', 'checked');
                }

                $label->appendChild($radio);

                $span = new XMLElement('span', $opt[2]);
                $label->appendChild($span);

                $fieldset->appendChild($label);
            }
        }

        $div->appendChild($fieldset);

        return $div;
    }
}
