<?php
/**
 * An Email field that essentially maps to HTML5's <input type="email" placeholder="$placeholder" multiple="$multiple" />
 *
 * @package fields
 * @see https://developer.mozilla.org/en-US/docs/Web/HTML/Reference/Elements/input/email
 * @author Tilo Schröder
 * @since 2.84.0
 * @license MIT
 * @link https://tiloschroeder.de
 */

class FieldEmail extends Field
{
    public function __construct()
    {
        parent::__construct();
        $this->_name = __('Email');
        $this->_required = true;
        $this->set('location', 'sidebar');
        $this->set('show_column', 'yes');
        $this->set('required', 'no');
    }

    /*-------------------------------------------------------------------------
        Setup:
    -------------------------------------------------------------------------*/

    protected static function getSortableWeekValue(string $weekValue): int {
        if (preg_match('/^(\d{4})-W(\d{2})$/', $weekValue, $matches)) {
            return (int) ($matches[1] . $matches[2]);
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

    public function createTable()
    {
        return Symphony::Database()->query(
            "CREATE TABLE IF NOT EXISTS `tbl_entries_data_" . $this->get('id') . "` (
                `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                `entry_id` int(11) UNSIGNED NOT NULL,
                `value` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
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

        // Placeholder
        $placeholder = new XMLElement('label', __('Placeholder <i>optional</i>'));
        $input = Widget::input('fields['.$this->get('sortorder').'][placeholder]', $this->get('placeholder'), 'text');
        $input->setAttribute('maxlength', '36');
        $placeholder->appendChild($input);

        $wrapper->appendChild($placeholder);

        $hint = new XMLElement('p', __('For the value and additional attributes see <a href="https://developer.mozilla.org/en-US/docs/Web/HTML/Reference/Elements/input/email" target="_blank" rel="noopener">MDN Web Docs</a>.'));
        $hint->setAttribute('class', 'help');
        $wrapper->appendChild($hint);

        // Requirements and table display
        $this->appendStatusFooter($wrapper);
    }

    public function commit()
    {
        if (!parent::commit()) return false;

        return FieldManager::saveSettings($this->get('id'), [
            'placeholder' => $this->get('placeholder'),
        ]);
    }

    /*-------------------------------------------------------------------------
        Input:
    -------------------------------------------------------------------------*/

    public function displayPublishPanel(XMLElement &$wrapper, $data = null, $flagWithError = null, $fieldnamePrefix = null, $fieldnamePostfix = null, $entry_id = null)
    {
        $value = isset($data['value']) ? $data['value'] : null;

        $input = Widget::input("fields{$fieldnamePrefix}[{$this->get('element_name')}]{$fieldnamePostfix}", $value, 'email');
        $input->setAttribute('spellcheck', 'false');
        $input->setAttribute('autocapitalize', 'off');
        if ($this->get('required') === 'yes') {
            $input->setAttribute('required', 'required');
        }
        if ($this->get('placeholder') !== null) {
            $input->setAttribute('placeholder', $this->get('placeholder'));
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

        // Return additional attribute 'placeholder'
        if ($this->get('placeholder') !== null) {
            $element->setAttribute('placeholder', $this->get('placeholder'));
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

        $input = Widget::input('fields['.$this->get('element_name').']', null, 'email');
        $input->setAttribute('id', $fieldName . '-' . $fieldId);
        $input->setAttribute('spellcheck', 'false');
        $input->setAttribute('autocapitalize', 'off');
        if ($this->get('required') === 'yes') {
            $input->setAttribute('required', 'required');
        }
        if ($this->get('placeholder') !== null) {
            $input->setAttribute('placeholder', $this->get('placeholder'));
        }
        $input->setAttribute('autocomplete', 'email');

        $div->appendChild($label);
        $div->appendChild($input);

        return $div;
    }

    public function checkPostFieldData($data, &$message, $entry_id = null)
    {
        $messages = array(
            'required' => __('‘%s’ is a required field.', array($this->get('label'))),
            'invalid' => __('Invalid email format.')
        );
        $message = null;

        if ($this->get('required') === 'yes' && strlen($data) === 0) {
            $message = $messages['required'];
            return self::__MISSING_FIELDS__;
        }

        if (strlen($data) !== 0) {
            if (!filter_var($data, FILTER_VALIDATE_EMAIL)) {
                $message = $messages['invalid'];
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
        Sorting:
    -------------------------------------------------------------------------*/

    public function buildSortingSQL(&$joins, &$where, &$sort, $order = 'ASC')
    {
        if ($this->isRandomOrder($order)) {
            $sort = 'ORDER BY RAND()';
        } else {
            $sort = sprintf(
                'ORDER BY (
                    SELECT %s
                    FROM tbl_entries_data_%d AS `ed`
                    WHERE entry_id = e.id
                ) %s, `e`.`id` %s',
                '`ed`.value',
                $this->get('id'),
                $order,
                $order
            );
        }
    }

    public function buildSortingSelectSQL($sort, $order = 'ASC')
    {
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
