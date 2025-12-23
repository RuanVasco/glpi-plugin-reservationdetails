<?php

namespace GlpiPlugin\Reservationdetails\Entity;

use CommonDropdown;
use Session;
use Glpi\Application\View\TemplateRenderer;

/**
 * Manages profile-based permissions for individual reservation items
 * Restricts which profiles can reserve specific items
 * Accessible via dropdown menu: Configurar > Listas Suspensas > Reservation Details > Permissões de Reserva
 */
class ItemPermission extends CommonDropdown {

    public static $rightname = 'plugin_reservationdetails_item_rules';

    public static function getTypeName($nb = 0) {
        return __('Permissões de Reserva');
    }

    public static function getTable($classname = null) {
        return 'glpi_plugin_reservationdetails_items_profiles';
    }

    public static function getIcon() {
        return 'fas fa-lock';
    }

    public static function getMenuName() {
        return __('Permissões de Reserva');
    }

    public static function canCreate(): bool {
        return Session::haveRight(self::$rightname, UPDATE);
    }

    public static function canView(): bool {
        return Session::haveRight(self::$rightname, READ);
    }

    public static function canUpdate(): bool {
        return Session::haveRight(self::$rightname, UPDATE);
    }

    public static function canDelete(): bool {
        return Session::haveRight(self::$rightname, UPDATE);
    }

    public static function canPurge(): bool {
        return Session::haveRight(self::$rightname, UPDATE);
    }

    /**
     * Get the form URL for this class
     */
    public static function getFormURL($full = true) {
        global $CFG_GLPI;

        if ($full) {
            return $CFG_GLPI['url_base'] . "/plugins/reservationdetails/front/item_permissions.form.php";
        }

        return $CFG_GLPI['root_doc'] . "/plugins/reservationdetails/front/item_permissions.form.php";
    }

    /**
     * Get the search URL for this class (used by dropdown menu)
     */
    public static function getSearchURL($full = true) {
        global $CFG_GLPI;

        if ($full) {
            return $CFG_GLPI['url_base'] . "/plugins/reservationdetails/front/item_permissions.php";
        }

        return $CFG_GLPI['root_doc'] . "/plugins/reservationdetails/front/item_permissions.php";
    }

    /**
     * Show the form for item permissions
     */
    public function showForm($ID, array $options = []) {
        global $DB;

        // Get all reservable items
        $reservableItems = [];
        $iterator = $DB->request([
            'SELECT' => ['id', 'itemtype', 'items_id', 'is_active'],
            'FROM'   => 'glpi_reservationitems',
            'WHERE'  => ['is_active' => 1],
            'ORDER'  => ['itemtype ASC', 'items_id ASC']
        ]);

        foreach ($iterator as $row) {
            $itemtype = $row['itemtype'];
            $items_id = $row['items_id'];
            $itemName = '';
            
            if (class_exists($itemtype)) {
                $item = new $itemtype();
                if ($item->getFromDB($items_id)) {
                    $itemName = $item->getName();
                }
            }
            
            if (empty($itemName)) {
                $itemName = "$itemtype #$items_id";
            }
            
            // Get friendly type name
            $typeName = class_exists($itemtype) ? $itemtype::getTypeName(1) : $itemtype;
            
            $reservableItems[] = [
                'id' => $row['id'],
                'name' => $itemName,
                'type' => $typeName,
                'profiles' => self::getProfilesForItem($row['id'])
            ];
        }

        // Get all profiles
        $profiles = [];
        $profileIterator = $DB->request([
            'SELECT' => ['id', 'name'],
            'FROM'   => 'glpi_profiles',
            'ORDER'  => ['name ASC']
        ]);
        foreach ($profileIterator as $row) {
            $profiles[$row['id']] = $row['name'];
        }

        $canEdit = Session::haveRight(self::$rightname, UPDATE);

        $loader = new TemplateRenderer();
        $loader->display(
            '@reservationdetails/item_permissions_form.html.twig',
            [
                'reservable_items' => $reservableItems,
                'profiles' => $profiles,
                'can_edit' => $canEdit,
                'itemtype' => $this
            ]
        );

        return true;
    }

    /**
     * Check if current user can reserve a specific item
     */
    public static function canUserReserveItem(int $reservationItemId): bool {
        global $DB;

        // Get allowed profiles for this item
        $allowedProfiles = self::getProfilesForItem($reservationItemId);

        // If no restrictions, anyone can reserve
        if (empty($allowedProfiles)) {
            return true;
        }

        // Get current user's profile
        $currentProfile = $_SESSION['glpiactiveprofile']['id'] ?? 0;

        return in_array($currentProfile, $allowedProfiles);
    }

    /**
     * Get list of profiles allowed to reserve a specific item
     */
    public static function getProfilesForItem(int $reservationItemId): array {
        global $DB;

        $profiles = [];
        $iterator = $DB->request([
            'SELECT' => 'profiles_id',
            'FROM'   => self::getTable(),
            'WHERE'  => ['reservationitems_id' => $reservationItemId]
        ]);

        foreach ($iterator as $row) {
            $profiles[] = (int)$row['profiles_id'];
        }

        return $profiles;
    }

    /**
     * Save allowed profiles for a specific item
     */
    public static function saveProfilesForItem(int $reservationItemId, array $profileIds): bool {
        global $DB;

        // Delete existing permissions for this item
        $DB->delete(self::getTable(), ['reservationitems_id' => $reservationItemId]);

        // Insert new permissions
        foreach ($profileIds as $profileId) {
            if ((int)$profileId > 0) {
                $DB->insert(self::getTable(), [
                    'reservationitems_id' => $reservationItemId,
                    'profiles_id'         => (int)$profileId
                ]);
            }
        }

        return true;
    }

    /**
     * Get all items that are restricted for the current user's profile
     */
    public static function getRestrictedItemsForUser(): array {
        global $DB;

        $currentProfile = $_SESSION['glpiactiveprofile']['id'] ?? 0;
        $restricted = [];

        // Get all items with restrictions
        $iterator = $DB->request([
            'SELECT'   => 'reservationitems_id',
            'DISTINCT' => true,
            'FROM'     => self::getTable()
        ]);

        foreach ($iterator as $row) {
            $itemId = (int)$row['reservationitems_id'];
            $allowedProfiles = self::getProfilesForItem($itemId);
            
            if (!in_array($currentProfile, $allowedProfiles)) {
                $restricted[] = $itemId;
            }
        }

        return $restricted;
    }
}
