<?php
/**
 * An Tel field that essentially maps to HTML5's <input type="tel" minlength="$min" maxlength="$max" placeholder="$placeholder" pattern="$pattern" />
 *
 * @package fields
 * @see https://developer.mozilla.org/en-US/docs/Web/HTML/Reference/Elements/input/tel
 * @author Tilo Schröder
 * @since 2.84.0
 * @license MIT
 * @link https://tiloschroeder.de
 */

class FieldTel extends Field
{
    public function __construct()
    {
        parent::__construct();
        $this->_name = __('Telephone');
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

    /**
     * Sorting is disabled because phone numbers are stored as free-form strings.
     * Typical formats (e.g. German phone numbers) include:
     * +49 40 123456
     * 0049-40-123456
     * 040 1234567
     * 0175-123456
     *
     * Due to the variety of formats, meaningful sorting is not possible.
     */
    public function isSortable()
    {
        return false;
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
            'value' => ['type' => 'DOUBLE', 'null' => true],
        ];
    }

    public function createTable()
    {
        return Symphony::Database()->query(
            "CREATE TABLE IF NOT EXISTS `tbl_entries_data_" . $this->get('id') . "` (
                `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                `entry_id` int(11) UNSIGNED NOT NULL,
                `value` VARCHAR(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
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
        $min = new XMLElement('label', __('Minlength <i>optional</i>'));
        $min->setAttribute('class', 'column');
        $min_input = Widget::input('fields['.$this->get('sortorder').'][minlength]', $this->get('minlength'), 'number');
        $min_input->setAttribute('min', '0');
        $min->appendChild($min_input);

        // Max
        $max = new XMLElement('label', __('Maxlength <i>optional</i>'));
        $max->setAttribute('class', 'column');
        $max_input = Widget::input('fields['.$this->get('sortorder').'][maxlength]', $this->get('maxlength'), 'number');
        $max_input->setAttribute('min', '0');
        $max->appendChild($max_input);

        $div = new XMLElement('div', null, array('class' => 'two columns'));
        $div->appendChild($min);
        $div->appendChild($max);
        $mm_hint = new XMLElement('p', __('Use the attributes <code>minlength</code> and <code>maxlength</code> in conjunction with <code>pattern</code> and <code>placeholder</code> to prevent incorrect entries by users as far as possible.'));
        $mm_hint->setAttribute('class', 'help');
        $wrapper->appendChild($mm_hint);
        $wrapper->appendChild($div);

        // Placeholder
        $placeholder = new XMLElement('label', __('Placeholder <i>optional</i>'));
        $placeholder->setAttribute('class', 'column');
        $placeholder_input = Widget::input('fields['.$this->get('sortorder').'][placeholder]', $this->get('placeholder'));
        $placeholder->appendChild($placeholder_input);

        // Pattern
        $pattern = new XMLElement('label', __('Pattern <i>optional</i>'));
        $pattern->setAttribute('class', 'column');
        $pattern_input = Widget::input('fields['.$this->get('sortorder').'][pattern]', $this->get('pattern'));
        $pattern_hint1 = new XMLElement('p', __('Leave blank to use the default pattern: digits, spaces, plus and minus signs only.'));
        $pattern_hint1->setAttribute('class', 'help');
        $pattern_hint2 = new XMLElement('p', __('A possible pattern for German phone numbers: <code>\d{3,5} \d{4,10}</code> or <code>[0-9]{3,5} [0-9]{4,10}</code><br/>When a custom pattern is defined, the default validation is disabled.'));
        $pattern_hint2->setAttribute('class', 'help');
        $pattern->appendChild($pattern_input);
        $pattern->appendChild($pattern_hint1);
        $pattern->appendChild($pattern_hint2);

        $div = new XMLElement('div', null, array('class' => 'two columns'));
        $div->appendChild($placeholder);
        $div->appendChild($pattern);
        $wrapper->appendChild($div);

        $hint = new XMLElement('p', __('For the value and additional attributes see <a href="https://developer.mozilla.org/en-US/docs/Web/HTML/Reference/Elements/input/tel" target="_blank" rel="noopener">MDN Web Docs</a>.'));
        $hint->setAttribute('class', 'help');
        $wrapper->appendChild($hint);

        // Requirements and table display
        $this->appendStatusFooter($wrapper);
    }

    public function commit()
    {
        if (!parent::commit()) return false;

        return FieldManager::saveSettings($this->get('id'), [
            'minlength' => $this->get('minlength'),
            'maxlength' => $this->get('maxlength'),
            'placeholder' => $this->get('placeholder'),
            'pattern' => $this->get('pattern'),
        ]);
    }

    /*-------------------------------------------------------------------------
        Input:
    -------------------------------------------------------------------------*/

    public function displayPublishPanel(XMLElement &$wrapper, $data = null, $flagWithError = null, $fieldnamePrefix = null, $fieldnamePostfix = null, $entry_id = null)
    {
        $value = isset($data['value']) ? $data['value'] : null;

        $input = Widget::input("fields{$fieldnamePrefix}[{$this->get('element_name')}]{$fieldnamePostfix}", $value, 'tel');
        if ($this->get('required') === 'yes') {
            $input->setAttribute('required', 'required');
        }
        if ($this->get('minlength') !== null) {
            $input->setAttribute('minlength', $this->get('minlength'));
        }
        if ($this->get('maxlength') !== null) {
            $input->setAttribute('maxlength', $this->get('maxlength'));
        }
        if ($this->get('placeholder') !== null) {
            $input->setAttribute('placeholder', $this->get('placeholder'));
        }
        if ($this->get('pattern') !== null) {
            $input->setAttribute('pattern', $this->get('pattern'));
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

        // Return additional attributes 'minlength', 'maxlength' 'placeholder' and 'pattern'
        if ($this->get('minlength') !== null) {
            $element->setAttribute('minlength', $this->get('minlength'));
        }

        if ($this->get('maxlength') !== null) {
            $element->setAttribute('maxlength', $this->get('maxlength'));
        }

        if ($this->get('placeholder') !== null) {
            $element->setAttribute('placeholder', $this->get('placeholder'));
        }

        if ($this->get('pattern') !== null) {
            $element->setAttribute('pattern', $this->get('pattern'));
        }

        $wrapper->appendChild($element);
    }

    public function getExampleFormMarkup()
    {
        $fieldId = $this->get('id');
        $fieldName = $this->get('element_name');

        $hint = '';
        if ($this->get('pattern') !== null) {
            $hint = "\n" . __('<!-- Note: The double curly brackets in the attribute `pattern` are not an error but necessary because XSLT interprets `{...}` as an expression -->');
        }

        $div = new XMLElement('div', $hint, array('class' => 'form-field'));

        $label = Widget::Label($this->get('label'));
        $label->setAttribute('for', $fieldName . '-' . $fieldId);
        if ($this->get('required') === 'yes') {
            $mark = new XMLElement('span', '*');
            $mark->setAttribute('aria-hidden', 'true');
            $mark->setAttribute('class', 'required-mark');
            $label->appendChild($mark);
        }

        $input = Widget::input('fields['.$this->get('element_name').']', null, 'tel');
        $input->setAttribute('id', $fieldName . '-' . $fieldId);

        // Return additional attributes 'minlength', 'maxlength' 'placeholder' and 'pattern'
        if ($this->get('minlength') !== null) {
            $input->setAttribute('minlength', $this->get('minlength'));
        }

        if ($this->get('maxlength') !== null) {
            $input->setAttribute('maxlength', $this->get('maxlength'));
        }

        if ($this->get('placeholder') !== null) {
            $input->setAttribute('placeholder', $this->get('placeholder'));
        }

        if ($this->get('pattern') !== null) {
            $input->setAttribute('pattern', str_replace( array( '{', '}' ), array( '{{', '}}' ), $this->get('pattern')));
            $input->setAttribute('inputmode', 'tel');
        }
        if ($this->get('required') === 'yes') {
            $input->setAttribute('required', 'required');
        }
        $input->setAttribute('autocomplete', 'tel');

        $div->appendChild($label);
        $div->appendChild($input);

        return $div;
    }

    public function checkPostFieldData($data, &$message, $entry_id = null)
    {
        $label = $this->get('label');
        $minlength = (int)$this->get('minlength');
        $maxlength = (int)$this->get('maxlength');
        $pattern = trim($this->get('pattern'));

        $messages = array(
            'required' => __('‘%s’ is a required field. ', [$label]),
            'minlength' => __('‘%s’ must be at least %d characters long.', array($label, $minlength)),
            'maxlength' => __('‘%s’ must be no longer than %d characters.', array($label, $maxlength)),
            'invalid' => __('Invalid pattern defined for ‘%s’.', array($label)),
            'format' => __('‘%s’ does not match the required format.', array($label)),
            'default_invalid' => __('‘%s’ must only contain digits, spaces, plus and minus signs.', array($label))
        );
        $message = null;


        if ($this->get('required') === 'yes' && strlen(trim($data)) === 0) {
            $message = $messages['required'];
            return self::__MISSING_FIELDS__;
        }

        // Skip further validation if empty and not required
        if (strlen(trim($data)) === 0) {
            return self::__OK__;
        }

            // 2. Min/Maxlength check
        $length = mb_strlen($data);
        if ($minlength > 0 && $length < $minlength) {
            $message = $messages['minlength'];
            return self::__INVALID_FIELDS__;
        }
        if ($maxlength > 0 && $length > $maxlength) {
            $message = $messages['maxlength'];
            return self::__INVALID_FIELDS__;
        }

        // 3. Pattern check
        if ($pattern !== '') {
            if (@preg_match('/' . $pattern . '/', '') === false) {
                $message = $messages['invalid'];
                return self::__INVALID_FIELDS__;
            }
            if (!preg_match('/^' . $pattern . '$/', $data)) {
                $message = $messages['format'];
                return self::__INVALID_FIELDS__;
            }
        } else {
            // 4. Default pattern (digits, +, -, spaces)
            if (!preg_match('/^[0-9+\-\s]+$/', $data)) {
                $message = $messages['default_invalid'];
                return self::__INVALID_FIELDS__;
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
