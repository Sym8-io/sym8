<?php
/**
 * A Range field that essentially maps to HTML5's <input type="range" min="$min" max="$max" step="$step" />
 *
 * @package fields
 * @see https://developer.mozilla.org/en-US/docs/Web/HTML/Reference/Elements/input/range
 * @author Tilo Schröder
 * @since 2.84.0
 * @license MIT
 * @link https://tiloschroeder.de
 */

class FieldRange extends Field implements ExportableField, ImportableField
{
    public function __construct()
    {
        parent::__construct();
        $this->_name = __('Range');
        $this->_required = true;
        $this->set('show_column', 'yes');
        $this->set('location', 'sidebar');
        $this->set('required', 'no');
    }

    /*-------------------------------------------------------------------------
        Setup:
    -------------------------------------------------------------------------*/

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
                `value` DOUBLE DEFAULT NULL,
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

        // Min
        $min = new XMLElement('label', __('Minimum Value <i>optional</i>'));
        $min->setAttribute('class', 'column');
        $min->appendChild(Widget::input('fields['.$this->get('sortorder').'][min]', $this->get('min'), 'number'));

        // Max
        $max = new XMLElement('label', __('Maximum Value <i>optional</i>'));
        $max->setAttribute('class', 'column');
        $max->appendChild(Widget::input('fields['.$this->get('sortorder').'][max]', $this->get('max'), 'number'));

        $div = new XMLElement('div', null, array('class' => 'two columns'));
        $div->appendChild($min);
        $div->appendChild($max);
        $wrapper->appendChild($div);

        // Step
        $step = new XMLElement('label', __('Step <i>optional</i>'));
        $step_input = Widget::input('fields['.$this->get('sortorder').'][step]', $this->get('step'));
        $step_input->setAttribute('placeholder', 'Default: 1');
        $step->appendChild($step_input);

        $wrapper->appendChild($step);

        // Tag group for step
        $buttons = new XMLElement('ul');
        $buttons->setAttribute('class', 'tags singular');
        $buttons->setAttribute('data-interactive', 'data-interactive');
        $buttons->appendChild(new XMLElement('li', '1'));
        $buttons->appendChild(new XMLElement('li', '2'));
        $buttons->appendChild(new XMLElement('li', '3'));
        $buttons->appendChild(new XMLElement('li', 'any'));

        $wrapper->appendChild($buttons);

        $hint = new XMLElement('p', __('For the value and additional attributes see <a href="https://developer.mozilla.org/en-US/docs/Web/HTML/Reference/Elements/input/range" target="_blank" rel="noopener">MDN Web Docs</a>.'));
        $hint->setAttribute('class', 'help');
        $wrapper->appendChild($hint);

        // Requirements and table display
        $this->appendStatusFooter($wrapper);
    }

    public function commit()
    {
        if (!parent::commit()) return false;

        return FieldManager::saveSettings($this->get('id'), [
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

        $input = Widget::input("fields{$fieldnamePrefix}[{$this->get('element_name')}]{$fieldnamePostfix}", $value, 'range');
        // `range` inputs always have a value and cannot be empty.
        // The `required` attribute is therefore not applicable.

        // Return additional attributes 'min', 'max' and 'step'
        if ($this->get('min') !== null) {
            $input->setAttribute('min', $this->get('min'));
        }

        if ($this->get('max') !== null) {
            $input->setAttribute('max', $this->get('max'));
        }

        if ($this->get('step') !== null) {
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

        $input = Widget::input('fields[' . $fieldName . ']', null, 'range');
        $input->setAttribute('id', $fieldName . '-' . $fieldId);
        // `range` inputs always have a value and cannot be empty.
        // The `required` attribute is therefore not applicable.

        // Return additional attributes 'min', 'max' and 'step'
        if ($this->get('min') !== null) {
            $input->setAttribute('min', $this->get('min'));
        }

        if ($this->get('max') !== null) {
            $input->setAttribute('max', $this->get('max'));
        }

        if ($this->get('step') !== null) {
            $input->setAttribute('step', $this->get('step'));
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
            'requrired' => __('‘%s’ is a required field.', array($this->get('label'))),
            'invalid' => __('Please enter a valid number.'),
            'min' => __('Range must be greater than or equal to %s.', array($min)),
            'max' => __('Range must be less than or equal to %s.', array($max)),
            'step' => __('Range must increase in steps of %s starting from %s.', array($step, $min))
        );
        $message = NULL;

        if ($this->get('required') == 'yes' && strlen($data) === 0) {
            $message = $messages['required'];
            return self::__MISSING_FIELDS__;
        }

        if (strlen($data) > 0 && !is_numeric($data)) {
            $message = $messages['invalid'];
            return self::__INVALID_FIELDS__;
        }

        if (is_numeric($data)) {
            if (is_numeric($min) && $data < $min) {
                $message = $messages['min'];
                return self::__INVALID_FIELDS__;
            }

            if (is_numeric($max) && $data > $max) {
                $message = $messages['max'];
                return self::__INVALID_FIELDS__;
            }

            if (is_numeric($step) && $step > 0) {
                $relative = $data - $min;
                if (fmod($relative, $step) !== 0.0) {
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
                'help'      => __('Enter a number, comparison (e.g. %s), or range (e.g. %s)', array(
                    '<code>&lt;123</code>',
                    '<code>100 to 200</code>'
                ))
            ),
            array(
                'title'     => 'less than',
                'filter'    => 'less than ',
                'help'      => __('Less than %s', array('<code>$x</code>'))
            ),
            array(
                'title'     => 'equal to or less than',
                'filter'    => 'equal to or less than ',
                'help'      => __('Equal to or less than %s', array('<code>$x</code>'))
            ),
            array(
                'title'     => 'greater than',
                'filter'    => 'greater than ',
                'help'      => __('Greater than %s', array('<code>$x</code>'))
            ),
            array(
                'title'     => 'equal to or greater than',
                'filter'    => 'equal to or greater than ',
                'help'      => __('Equal to or greater than %s', array('<code>$x</code>'))
            )
        );
    }

    public function buildDSRetrievalSQL($data, &$joins, &$where, $andOperation = false)
    {
        $field_id = $this->get('id');
        $expression = " `t$field_id`.`value` ";

        // X to Y support
        if (preg_match('/^(-?(?:\d+(?:\.\d+)?|\.\d+)) to (-?(?:\d+(?:\.\d+)?|\.\d+))$/i', $data[0], $match)) {

            $joins .= " LEFT JOIN `tbl_entries_data_$field_id` AS `t$field_id` ON (`e`.`id` = `t$field_id`.entry_id) ";
            $where .= " AND CAST(`t$field_id`.`value` AS FLOAT) BETWEEN {$match[1]} AND {$match[2]} ";


        }

        // Equal to or less/greater than X
        elseif (preg_match('/^(equal to or )?(less|greater) than\s*(-?(?:\d+(?:\.\d+)?|\.\d+))$/i', $data[0], $match)) {

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

            $expression .= " {$match[3]} ";

            $joins .= " LEFT JOIN `tbl_entries_data_$field_id` AS `t$field_id` ON (`e`.`id` = `t$field_id`.entry_id) ";
            $where .= " AND $expression ";

        }

        // Look for <=/< or >=/> symbols
        elseif (preg_match('/^(=?[<>]=?)\s*(-?(?:\d+(?:\.\d+)?|\.\d+))$/i', $data[0], $match)) {

            $joins .= " LEFT JOIN `tbl_entries_data_$field_id` AS `t$field_id` ON (`e`.`id` = `t$field_id`.entry_id) ";
            $where .= sprintf(
                " AND %s %s %f",
                $expression,
                $match[1],
                $match[2]
            );

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
