<?php

define('PLUGIN_RESERVATIONDETAILS_VERSION', '0.1.0');

function plugin_init_reservationdetails() {
    global $PLUGIN_HOOKS;

    $PLUGIN_HOOKS['csrf_compliant']['reservationdetails'] = true;
}

function plugin_version_reservationdetails() {
    return [
        'name'           => 'Reservation Details',
        'version'        => PLUGIN_RESERVATIONDETAILS_VERSION,
        'author'         => 'Ruan Vasconcelos',
        'license'        => 'MIT',
        'homepage'       => 'https://github.com/RuanVasco',
        'requirements'   => [
            'glpi'   => [
                'min' => '11.0.4',
                'max' => '11.0.4'
            ]
        ],
        'description'   => 'Adds contextual fields to the asset reservation form.'
   ];
}

function plugin_reservationdetails_check_prerequisites() {
    if (version_compare(GLPI_VERSION, '11.0.4', 'lt')) {
        echo "This plugin requires GLPI >= 11.0.4";
        return false;
    }
   return true;
}

