<?php

/*
Plugin Name: Custom AJAX Importer
Description: A plugin to forward AJAX data to an existing plugin's action and create a custom REST API endpoint.
Version: 1.1
Author: Hamza
*/


if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

use We_Cpt_Betting_Site as CptBettingSite;
use We_Cpt_Betting_Site_Meta_Keys as MetaKeys;
use We_Taxonomy as Taxonomy;
use We_M_Betting_Site as BettingSite;

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/includes/primary_import.php';
require_once __DIR__ . '/includes/helpers.php';


use Monolog\Level;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

// Handle the incoming POST request via the custom REST API endpoint
function custom_import_endpoint_handler(WP_REST_Request $request)
{

    $log = new Logger('data-sync');
    $log->pushHandler(new StreamHandler(__DIR__ . '/logs/sync.log', Level::Info));

    $payload = $request->get_param('data');

    // Forward each data item to the existing plugin's action
    foreach ($payload as $key => $betting_site_data) {

        if (! empty($betting_site_data['Name'])) {
            primary_import($betting_site_data);
        }


        break;
    }

    return new WP_REST_Response(['success' => true, 'results' => $results], 200);
}

// Register the REST API route
function custom_import_register_routes()
{
    register_rest_route('custom-import/v1', '/import', array(
        'methods' => 'POST',
        'callback' => 'custom_import_endpoint_handler',
        'permission_callback' => '__return_true',
    ));
}
add_action('rest_api_init', 'custom_import_register_routes');
