<?php

namespace GlpiPlugin\Reservationdetails\Entity;

use CommonDBTM;
use Html;
use Session;
use Glpi\Application\View\TemplateRenderer;

class CustomField extends CommonDBTM {
    public static $rightname = 'plugin_reservationdetails_customfields';

    public static function getTypeName($nb = 0) {
        return _n('Custom Field', 'Custom Fields', $nb);
    }

    public static function getTable($classname = null) {
        return 'glpi_plugin_reservationdetails_customfields';
    }

    public static function getIcon() {
        return 'fas fa-list-alt';
    }

    public static function canCreate(): bool {
        return Session::haveRight(self::$rightname, CREATE);
    }

    public static function canView(): bool {
        return Session::haveRight(self::$rightname, READ);
    }

    public static function canUpdate(): bool {
        return Session::haveRight(self::$rightname, UPDATE);
    }

    public static function canDelete(): bool {
        return Session::haveRight(self::$rightname, DELETE);
    }

    public static function canPurge(): bool {
        return Session::haveRight(self::$rightname, PURGE);
    }

    public static function getMenuName() {
        return self::getTypeName(2);
    }

    static function getFormURL($full = true) {
        global $CFG_GLPI;

        if ($full) {
            return $CFG_GLPI['url_base'] . "/plugins/reservationdetails/front/customfield.form.php";
        }

        return $CFG_GLPI['root_doc'] . "/plugins/reservationdetails/front/customfield.form.php";
    }

    static function getSearchURL($full = true) {
        global $CFG_GLPI;

        if ($full) {
            return $CFG_GLPI['url_base'] . "/plugins/reservationdetails/front/customfield.php";
        }

        return $CFG_GLPI['root_doc'] . "/plugins/reservationdetails/front/customfield.php";
    }

    public function rawSearchOptions() {
        $tab = [];

        $tab[] = [
            'id'   => 'common',
            'name' => self::getTypeName(2)
        ];

        $tab[] = [
            'id'            => '1',
            'table'         => self::getTable(),
            'field'         => 'field_label',
            'name'          => __('Name'),
            'datatype'      => 'itemlink',
            'massiveaction' => false
        ];

        $tab[] = [
            'id'            => '2',
            'table'         => self::getTable(),
            'field'         => 'id',
            'name'          => __('ID'),
            'massiveaction' => false,
            'datatype'      => 'number'
        ];

        $tab[] = [
            'id'            => '10',
            'table'         => self::getTable(),
            'field'         => 'itemtype',
            'name'          => __('Item Type'),
            'datatype'      => 'string',
            'massiveaction' => true
        ];

        $tab[] = [
            'id'            => '11',
            'table'         => self::getTable(),
            'field'         => 'field_name',
            'name'          => __('Field Name'),
            'datatype'      => 'string',
            'massiveaction' => false
        ];

        $tab[] = [
            'id'            => '13',
            'table'         => self::getTable(),
            'field'         => 'field_type',
            'name'          => __('Type'),
            'datatype'      => 'string',
            'massiveaction' => true
        ];

        $tab[] = [
            'id'            => '14',
            'table'         => self::getTable(),
            'field'         => 'is_mandatory',
            'name'          => __('Mandatory'),
            'datatype'      => 'bool',
            'massiveaction' => true
        ];

        return $tab;
    }

    public function showForm($ID, array $options = []) {
        $this->initForm($ID, $options);
        
        $loader = new TemplateRenderer();
        $loader->display('@reservationdetails/customfield_form.html.twig', [
            'item'   => $this,
            'params' => $options,
            'itemtypes' => self::getReservableItemTypes()
        ]);

        return true;
    }

    /**
     * Get all item types that can be reserved
     */
    public static function getReservableItemTypes(): array {
        global $DB;

        $types = [];
        
        $iterator = $DB->request([
            'SELECT'   => ['itemtype'],
            'DISTINCT' => true,
            'FROM'     => 'glpi_reservationitems',
            'WHERE'    => ['is_active' => 1]
        ]);

        foreach ($iterator as $row) {
            $itemtype = $row['itemtype'];
            if (class_exists($itemtype)) {
                $types[$itemtype] = $itemtype::getTypeName(1);
            }
        }

        asort($types);
        return $types;
    }

    /**
     * Get custom fields for a specific itemtype
     */
    public static function getFieldsForItemType(string $itemtype): array {
        global $DB;

        $fields = [];

        $iterator = $DB->request([
            'FROM'  => self::getTable(),
            'WHERE' => ['itemtype' => $itemtype],
            'ORDER' => 'field_order ASC'
        ]);

        foreach ($iterator as $row) {
            $fields[] = $row;
        }

        return $fields;
    }

    /**
     * Get dropdown values as array
     */
    public function getDropdownValuesArray(): array {
        if (empty($this->fields['dropdown_values'])) {
            return [];
        }

        $lines = explode("\n", $this->fields['dropdown_values']);
        $values = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if (!empty($line)) {
                $values[$line] = $line;
            }
        }

        return $values;
    }
}
