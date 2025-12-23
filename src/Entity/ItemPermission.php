<?php

namespace GlpiPlugin\Reservationdetails\Entity;

use CommonDBTM;
use CommonGLPI;
use Session;
use Html;

/**
 * Manages profile-based permissions for asset types (itemtypes)
 * Restricts which profiles can reserve items of specific asset definitions
 */
class ItemPermission extends CommonDBTM {

    public static $rightname = 'config';

    public static function getTypeName($nb = 0) {
        return __('Permissões de Reserva');
    }

    public static function getTable($classname = null) {
        return 'glpi_plugin_reservationdetails_itemtypes_profiles';
    }

    public static function getIcon() {
        return 'fas fa-user-lock';
    }

    public static function getMenuName() {
        return __('Permissões de Reserva');
    }

    public static function getSearchURL($full = true) {
        return PLUGIN_RESERVATIONDETAILS_WEBDIR . '/front/itemtype_permissions.php';
    }

    /**
     * Check if current user can reserve items of given type
     */
    public static function canUserReserveItemtype(string $itemtype): bool {
        global $DB;

        // Get allowed profiles for this itemtype
        $allowedProfiles = self::getProfilesForItemtype($itemtype);

        // If no restrictions, anyone can reserve
        if (empty($allowedProfiles)) {
            return true;
        }

        // Get current user's profile
        $currentProfile = $_SESSION['glpiactiveprofile']['id'] ?? 0;

        return in_array($currentProfile, $allowedProfiles);
    }

    /**
     * Get list of profiles allowed to reserve an itemtype
     */
    public static function getProfilesForItemtype(string $itemtype): array {
        global $DB;

        $profiles = [];
        $iterator = $DB->request([
            'SELECT' => 'profiles_id',
            'FROM'   => self::getTable(),
            'WHERE'  => ['itemtype' => $itemtype]
        ]);

        foreach ($iterator as $row) {
            $profiles[] = (int)$row['profiles_id'];
        }

        return $profiles;
    }

    /**
     * Save allowed profiles for an itemtype
     */
    public static function saveProfilesForItemtype(string $itemtype, array $profileIds): bool {
        global $DB;

        // Delete existing permissions
        $DB->delete(self::getTable(), ['itemtype' => $itemtype]);

        // Insert new permissions
        foreach ($profileIds as $profileId) {
            if ((int)$profileId > 0) {
                $DB->insert(self::getTable(), [
                    'itemtype'    => $itemtype,
                    'profiles_id' => (int)$profileId
                ]);
            }
        }

        return true;
    }

    /**
     * Get all itemtypes that user cannot reserve
     */
    public static function getRestrictedItemtypesForUser(): array {
        global $DB;

        $currentProfile = $_SESSION['glpiactiveprofile']['id'] ?? 0;
        $restricted = [];

        // Get all itemtypes that have restrictions
        $iterator = $DB->request([
            'SELECT'   => 'itemtype',
            'DISTINCT' => true,
            'FROM'     => self::getTable()
        ]);

        foreach ($iterator as $row) {
            $itemtype = $row['itemtype'];
            $allowedProfiles = self::getProfilesForItemtype($itemtype);
            
            if (!in_array($currentProfile, $allowedProfiles)) {
                $restricted[] = $itemtype;
            }
        }

        return $restricted;
    }

    /**
     * Get all itemtypes with their permission settings
     */
    public static function getAllItemtypePermissions(): array {
        global $DB;

        $permissions = [];
        $iterator = $DB->request([
            'SELECT'   => ['itemtype', 'profiles_id'],
            'FROM'     => self::getTable(),
            'ORDER'    => ['itemtype']
        ]);

        foreach ($iterator as $row) {
            $itemtype = $row['itemtype'];
            if (!isset($permissions[$itemtype])) {
                $permissions[$itemtype] = [];
            }
            $permissions[$itemtype][] = (int)$row['profiles_id'];
        }

        return $permissions;
    }
}
