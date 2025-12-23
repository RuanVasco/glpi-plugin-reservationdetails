<?php

namespace GlpiPlugin\Reservationdetails\Entity;

use CommonGLPI;
use Profile as GlpiProfile;
use ProfileRight;
use Session;
use Html;

class Profile extends CommonGLPI {

    public static $rightname = 'profile';

    public static function getTypeName($nb = 0) {
        return __('Reservation Details');
    }

    public static function getIcon() {
        return 'fas fa-calendar-alt';
    }

    /**
     * Get all rights defined by the plugin
     */
    public static function getAllRights(): array {
        return [
            [
                'itemtype'  => Resource::class,
                'label'     => Resource::getTypeName(2),
                'field'     => Resource::$rightname,
                'rights'    => [
                    READ    => __('Read'),
                    UPDATE  => __('Update'),
                    CREATE  => __('Create'),
                    DELETE  => __('Delete'),
                    PURGE   => __('Delete permanently')
                ]
            ],
            [
                'itemtype'  => Reservation::class,
                'label'     => __('Reservations (plugin)'),
                'field'     => Reservation::$rightname,
                'rights'    => [
                    READ    => __('Read'),
                    CREATE  => __('Create')
                ]
            ],
            [
                'itemtype'  => CustomField::class,
                'label'     => CustomField::getTypeName(2),
                'field'     => CustomField::$rightname,
                'rights'    => [
                    READ    => __('Read'),
                    UPDATE  => __('Update'),
                    CREATE  => __('Create'),
                    DELETE  => __('Delete'),
                    PURGE   => __('Delete permanently')
                ]
            ],
            [
                'itemtype'  => ItemPermission::class,
                'label'     => __('Permissões de Reserva'),
                'field'     => ItemPermission::$rightname,
                'rights'    => [
                    READ    => __('Read'),
                    UPDATE  => __('Update')
                ]
            ]
        ];
    }

    /**
     * Get tab name for profile item
     */
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
        if ($item instanceof GlpiProfile) {
            return self::getTypeName();
        }
        return '';
    }

    /**
     * Display tab content for profile
     */
    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
        if ($item instanceof GlpiProfile) {
            self::showForProfile($item);
        }
        return true;
    }

    /**
     * Show rights form for profile
     */
    public static function showForProfile(GlpiProfile $profile): void {
        $profileId = $profile->getID();
        $canEdit = Session::haveRight('profile', UPDATE);

        echo "<div class='spaced'>";

        if ($canEdit && $profileId) {
            echo "<form method='post' action='" . GlpiProfile::getFormURL() . "'>";
        }

        $rights = self::getAllRights();
        $matrix = [];

        foreach ($rights as $right) {
            $matrix[] = [
                'itemtype'   => $right['itemtype'],
                'label'      => $right['label'],
                'field'      => $right['field'],
                'rights'     => $right['rights']
            ];
        }

        $profile->displayRightsChoiceMatrix(
            $matrix,
            [
                'canedit'       => $canEdit,
                'default_class' => 'tab_bg_2',
                'title'         => self::getTypeName()
            ]
        );

        if ($canEdit && $profileId) {
            echo "<div class='center'>";
            echo Html::hidden('id', ['value' => $profileId]);
            echo Html::submit(_sx('button', 'Save'), ['name' => 'update', 'class' => 'btn btn-primary']);
            echo "</div>";
            Html::closeForm();
        }

        echo "</div>";
    }

    /**
     * Add rights to profile during plugin installation
     */
    public static function installRights(): void {
        $rights = self::getAllRights();

        foreach ($rights as $right) {
            ProfileRight::addProfileRights([$right['field']]);
        }
    }

    /**
     * Remove rights from profiles during plugin uninstallation
     */
    public static function uninstallRights(): void {
        $rights = self::getAllRights();

        foreach ($rights as $right) {
            ProfileRight::deleteProfileRights([$right['field']]);
        }
    }
}
