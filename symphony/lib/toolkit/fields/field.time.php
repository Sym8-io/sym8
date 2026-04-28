<?php
/**
 * A Time field that essentially maps to HTML5's <input type="time" min="$min" max="$max" step="$step" />
 *
 * @package fields
 * @see https://developer.mozilla.org/en-US/docs/Web/HTML/Reference/Elements/input/time
 * @author Tilo Schröder
 * @since 2.84.0
 * @license MIT
 * @link https://tiloschroeder.de
 */

class FieldTime extends Field
{
    public function __construct()
    {
        parent::__construct();
        $this->_name = __('Date: Time');
        $this->_required = true;
        $this->set('show_column', 'yes');
        $this->set('location', 'sidebar');
        $this->set('required', 'no');
    }

    /*-------------------------------------------------------------------------
        Setup:
    -------------------------------------------------------------------------*/

    protected static function getSortableTimeValue(string $timeValue): int
    {
        if (preg_match('/^(\d{2}):(\d{2})$/', $timeValue, $matches)) {
            return (int) ($matches[1] . $matches[2]);
        }
        return 0;
    }

    protected static function getTimeInSeconds(string $timeValue): int
    {
        if (preg_match('/^(\d{2}):(\d{2})(?::(\d{2}))?$/', $timeValue, $matches)) {
            $hours = (int)$matches[1];
            $minutes = (int)$matches[2];
            $seconds = isset($matches[3]) ? (int)$matches[3] : 0;
            return $hours * 3600 + $minutes * 60 + $seconds;
        }
        return 0;
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
        return true;
    }

    public function allowDatasourceParamOutput()
    {
        return true;
    }

    public function prepareTableValue($data, XMLElement $link = null, $entry_id = null)
    {
        return parent::prepareTableValue(['value' => $data['value'] ?? null], $link, $entry_id);
    }

    public function getDatabaseSchema()
    {
        return [
            'value' => array('type' => 'DOUBLE', 'null' => true),
        ];
    }

    public function createTable()
    {
        return Symphony::Database()->query(
            "CREATE TABLE IF NOT EXISTS `tbl_entries_data_" . $this->get('id') . "` (
                `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                `entry_id` int(11) UNSIGNED NOT NULL,
                `value` VARCHAR(8) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                PRIMARY KEY  (`id`),
                UNIQUE KEY `entry_id` (`entry_id`),
                KEY `value` (`value`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }

    /*-------------------------------------------------------------------------
        Settings:
    -------------------------------------------------------------------------*/

    public function displaySettingsPanel(XMLElement &$wrapper, $errors = null)
    {
        parent::displaySettingsPanel($wrapper, $errors);

        // Default time (checkbox)
        $default = new XMLElement('label', __('Set current time as value'));
        $default->setAttribute('class', 'column');
        $input = Widget::Input('fields['.$this->get('sortorder').'][default_time]', 'on', 'checkbox');
        if ($this->get('default_time') === 'on') {
            $input->setAttribute('checked', 'checked');
        }
        $default->appendChild($input);

        // Custom time
        $custom = new XMLElement('label', __('Custom time <i>optional</i>'));
        $custom->setAttribute('class', 'column');
        $input = Widget::input('fields['.$this->get('sortorder').'][custom_time]', $this->get('custom_time'), 'time');
        $custom->appendChild($input);

        $div = new XMLElement('div', null, array('class' => 'two columns'));
        $div->appendChild($default);
        $div->appendChild($custom);
        $wrapper->appendChild($div);

        // Min
        $min = new XMLElement('label', __('Earliest time to accept <i>optional</i>'));
        $min->setAttribute('class', 'column');
        $input = Widget::input('fields['.$this->get('sortorder').'][min]', $this->get('min'), 'time');
        $min->appendChild($input);

        // Max
        $max = new XMLElement('label', __('Latest time <i>optional</i>'));
        $max->setAttribute('class', 'column');
        $input = Widget::input('fields['.$this->get('sortorder').'][max]', $this->get('max'), 'time');
        $max->appendChild($input);

        $div = new XMLElement('div', null, array('class' => 'two columns'));
        $div->appendChild($min);
        $div->appendChild($max);
        $wrapper->appendChild($div);

        // Step
        $step = new XMLElement('label', __('Step <i>optional</i>'));
        $step_input = Widget::input('fields['.$this->get('sortorder').'][step]', $this->get('step'));
        $step_input->setAttribute('placeholder', 'Default: 60');
        $step->appendChild($step_input);

        $wrapper->appendChild($step);

        // Tag group for step
        $buttons = new XMLElement('ul');
        $buttons->setAttribute('class', 'tags singular');
        $buttons->setAttribute('data-interactive', 'data-interactive');

        $examples = array(
            '60'    => '1 min.',
            '600'   => '10 min.',
            '3600'  => '1 h',
            '7200'  => '2 h',
            'any'   => 'any'
        );

        foreach ($examples as $example => $label) {
            $li = new XMLElement('li', $example);
            $li->setAttribute('title', $label);
            $buttons->appendChild($li);
        }

        $wrapper->appendChild($buttons);

        $hint = new XMLElement('p', __('For the formatted value and additional attributes see <a href="https://developer.mozilla.org/en-US/docs/Web/HTML/Reference/Elements/input/time" target="_blank" rel="noopener">MDN Web Docs</a>.'));
        $hint->setAttribute('class', 'help');
        $wrapper->appendChild($hint);

        // Requirements and table display
        $this->appendStatusFooter($wrapper);
    }

    public function commit()
    {
        if (!parent::commit()) return false;

        return FieldManager::saveSettings($this->get('id'), [
            'default_time' => $this->get('default_time') === 'on' ? 'on' : 'off',
            'custom_time' => $this->get('custom_time'),
            'min' => $this->get('min'),
            'max' => $this->get('max'),
            'step' => $this->get('step'),
        ]);
    }

    /*-------------------------------------------------------------------------
        Input:
    -------------------------------------------------------------------------*/

    public function displayPublishPanel(XMLElement &$wrapper, $data = null, $flagWithError = null, $fieldnamePrefix = null, $fieldnamePostfix = null, $entry_id = null)
    {
        $value = isset($data['value']) ? $data['value'] : null;

        $input = Widget::input("fields{$fieldnamePrefix}[{$this->get('element_name')}]{$fieldnamePostfix}", $value, 'time');
        if ($value === null and $this->get('default_time') === 'on') {
            $input->setAttribute('value', date('H:i'));
        }
        elseif ($value === null and $this->get('custom_time') !== null) {
            $input->setAttribute('value', $this->get('custom_time'));
        }
        if ($this->get('required') === 'yes') {
            $input->setAttribute('required', 'required');
        }

        // Return additional attributes 'min', 'max' and 'step'
        if ($this->get('min') !== null) {
            $input->setAttribute('min', $this->get('min'));
        }

        if ($this->get('max') !== null) {
            $input->setAttribute('max', $this->get('max'));
        }

        if ($this->get('step') !== '60'){
            $input->setAttribute('step', $this->get('step'));
        }

        $label = Widget::label($this->get('label'));
        if ($this->get('required') !== 'yes') {
            $label->appendChild(new XMLElement('i', __('Optional')));
        }
        $label->appendChild($input);

        if ($flagWithError != null) {
            $wrapper->appendChild(Widget::Error($label, $flagWithError));
        } else {
            $wrapper->appendChild($label);
        }
    }

    public function appendFormattedElement(XMLElement &$wrapper, $data, $encode = false, $mode = null, $entry_id = null)
    {
        $value = (isset($data['value']) && is_scalar($data['value'])) ? $data['value'] : '';

        $element = new XMLElement($this->get('element_name'), $value);

        // Return additional attributes 'min', 'max' and 'step'
        if ($this->get('min') !== null) {
            $element->setAttribute('min', $this->get('min'));
        }

        if ($this->get('max') !== null) {
            $element->setAttribute('max', $this->get('max'));
        }

        if ($this->get('step') !== null) {
            $element->setAttribute('step', $this->get('step'));
        }

        $wrapper->appendChild($element);
    }

    public function getExampleFormMarkup()
    {
        $fieldId = $this->get('id');
        $fieldName = $this->get('element_name');

        $div = new XMLElement('div', null, array('class' => 'form-field'));
        $label = Widget::Label($this->get('label'));
        $label->setAttribute('for', $fieldName . '-' . $fieldId);
        if ($this->get('required') === 'yes') {
            $mark = new XMLElement('span', '*');
            $mark->setAttribute('aria-hidden', 'true');
            $mark->setAttribute('class', 'required-mark');
            $label->appendChild($mark);
        }

        $input = Widget::input('fields[' . $fieldName . ']', null, 'time');
        $input->setAttribute('id', $fieldName . '-' . $fieldId);
        if ($this->get('custom_time') !== null) {
            $input->setAttribute('value', $this->get('custom_time'));
        }
        if ($this->get('default_time') === 'on') {
            $input->setAttribute('value', '{/data/params/current-time}');
        }
        if ($this->get('required') === 'yes') {
            $input->setAttribute('required', 'required');
        }
        if ($this->get('step') !== '60') {
            $input->setAttribute('step', $this->get('step'));
        }
        if ($this->get('min') !== null) {
            $input->setAttribute('min', $this->get('min'));
        }
        if ($this->get('max') !== null) {
            $input->setAttribute('max', $this->get('max'));
        }

        $div->appendChild($label);
        $div->appendChild($input);

        return $div;
    }

    public function checkPostFieldData($data, &$message, $entry_id = null)
    {
        $min = $this->get('min');
        $max = $this->get('max');
        $step = $this->get('step');

        $messages = array(
            'required' => __('‘%s’ is a required field.', array($this->get('label'))),
            'invalid' => __('Please enter a valid time in format "hh:mm".'),
            'hours' => __('Please enter a valid time in format "hh:mm".'),
            'minutes' => __('The number of minutes must not be greater than or equal to 60.'),
            'seconds' => __('The number of seconds must not be greater than or equal to 60.'),
            'min' => __('Time must be greater than or equal to %s.', array($min)),
            'max' => __('Time must be less than or equal to %s.', array($max)),
            'step' => __('Time must increase in steps of %s seconds starting from %s.', array($step, $min ?? '00:00'))
        );
        $message = null;

        if ($this->get('required') === 'yes' && strlen($data) === 0) {
            $message =$messages['required'];
            return self::__MISSING_FIELDS__;
        }

        if (strlen($data) > 0 && !preg_match('/^(\d{2}):(\d{2})(?::(\d{2}))?$/', $data)) {
            $message = $messages['invalid'];
            return self::__INVALID_FIELDS__;
        }

        if (preg_match('/^(\d{2}):(\d{2})(?::(\d{2}))?$/', $data, $matches)) {
            $hour = (int)$matches[1];
            $minute = (int)$matches[2];
            $second = isset($matches[3]) ? (int)$matches[3] : 0;

            if ($hour >= 24) {
                $message = $messages['hours'];
                return self::__INVALID_FIELDS__;
            }
            elseif ($minute >= 60) {
                $message = $messages['minutes'];
                return self::__INVALID_FIELDS__;
            }
            elseif ($second >= 60) {
                $message = $messages['seconds'];
                return self::__INVALID_FIELDS__;
            }
        }

        if (preg_match('/^(\d{2}):(\d{2})(?::(\d{2}))?$/', $data)) {
            if ($min !== null && $data < $min) {
                $message = $messages['min'];
                return self::__INVALID_FIELDS__;
            }

            if ($max !== null && $data > $max) {
                $message = $messages['max'];
                return self::__INVALID_FIELDS__;
            }

            if ($step !== 'any' && $step > 0) {
                $step = (int)$step;
                $tmpData = static::getTimeInSeconds($data);
                $tmpMin = static::getTimeInSeconds($min ?? '00:00');
                $relative = $tmpData - $tmpMin;
                if ($relative < 0 || fmod($relative, $step) !== 0.0) {
                    $message = $messages['step'];
                    return self::__INVALID_FIELDS__;
                }
            }
        }

        return self::__OK__;
    }

    public function processRawFieldData($data, &$status, &$message=null, $simulate = false, $entry_id = null)
    {
        $status = self::__OK__;

        if (strlen(trim($data)) == 0) return array();

        $result = array(
            'value' => $data
        );

        return $result;
    }


    /*-------------------------------------------------------------------------
        Import:
    -------------------------------------------------------------------------*/

    public function getImportModes()
    {
        return array(
            'getValue' =>		ImportableField::STRING_VALUE,
            'getPostdata' =>	ImportableField::ARRAY_VALUE
        );
    }

    public function prepareImportValue($data, $mode, $entry_id = null)
    {
        $message = $status = null;
        $modes = (object)$this->getImportModes();

        if ($mode === $modes->getValue) {
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
            'getUnformatted' => ExportableField::UNFORMATTED,
            'getPostdata' =>	ExportableField::POSTDATA
        );
    }

    /**
    * Give the field some data and ask it to return a value using one of many
    * possible modes.
    *
    * @param mixed $data
    * @param integer $mode
    * @param integer $entry_id
    * @return string|null
    */
    public function prepareExportValue($data, $mode, $entry_id = null)
    {
        $modes = (object)$this->getExportModes();

        // Export unformatted:
        if ($mode === $modes->getUnformatted || $mode === $modes->getPostdata) {
            return isset($data['value'])
                ? $data['value']
                : null;
        }

        return null;
    }

    /*-------------------------------------------------------------------------
        Filtering:
    -------------------------------------------------------------------------*/

    /**
    * Returns the keywords that this field supports for filtering. Note
    * that no filter will do a simple 'straight' match on the value.
    *
    * @since Symphony 2.6.0
    * @return array
    */
    public function fetchFilterableOperators()
    {
        return array(
            array(
                'title'     => 'is',
                'filter'    => ' ',
                'help'      => __('Find values that are an exact match for the given time (e.g. %s)', array('<code>hh:mm</code>'))
            ),
            array(
                'title'     => 'less than',
                'filter'    => 'less than ',
                'help'      => __('Less than %s', array('<code>hh:mm</code>'))
            ),
            array(
                'title'     => 'equal to or less than',
                'filter'    => 'equal to or less than ',
                'help'      => __('Equal to or less than %s', array('<code>hh:mm</code>'))
            ),
            array(
                'title'     => 'greater than',
                'filter'    => 'greater than ',
                'help'      => __('Greater than %s', array('<code>hh:mm</code>'))
            ),
            array(
                'title'     => 'equal to or greater than',
                'filter'    => 'equal to or greater than ',
                'help'      => __('Equal to or greater than %s', array('<code>hh:mm</code>'))
            )
        );
    }

    public function buildDSRetrievalSQL($data, &$joins, &$where, $andOperation = false)
    {
        $field_id = $this->get('id');
        #$expression = " `t$field_id`.`value` ";
        $expression = "CAST(REPLACE(t$field_id.value, ':', '') AS UNSIGNED) ";

        // Equal to or less/greater than X
        if (preg_match('/^(equal to or )?(less|greater) than\s*(\d{2}:\d{2})$/i', $data[0], $match)) {

            switch($match[2]) {
                case 'less':
                    $expression .= '<';
                    break;

                case 'greater':
                    $expression .= '>';
                    break;
            }

            if ($match[1]) {
                $expression .= '=';
            }

            $value = static::getSortableTimeValue($match[3]);
            $expression .= " {$value}";

            $joins .= " LEFT JOIN `tbl_entries_data_$field_id` AS `t$field_id` ON (`e`.`id` = `t$field_id`.entry_id) ";
            $where .= " AND $expression ";
        }

        else parent::buildDSRetrievalSQL($data, $joins, $where, $andOperation);

        return true;
    }

    /*-------------------------------------------------------------------------
        Grouping:
    -------------------------------------------------------------------------*/

    public function groupRecords($records)
    {
        if (!is_array($records) || empty($records)) return;

        $groups = array($this->get('element_name') => array());

        foreach ($records as $r) {
            $data = $r->getData($this->get('id'));

            $value = $data['value'];

            if (!isset($groups[$this->get('element_name')][$value])) {
                $groups[$this->get('element_name')][$value] = array(
                    'attr' => array('value' => $value),
                    'records' => array(),
                    'groups' => array()
                );
            }

            $groups[$this->get('element_name')][$value]['records'][] = $r;

        }

        return $groups;
    }

}
