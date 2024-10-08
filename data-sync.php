<?php

/*
Plugin Name: Data Sync
Description: A plugin to sync data to wordpress websites using custom API endpoint.
Version: 1.1
Author: Hamza Siddique
Phone: https://wa.me/3115483343
*/


if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/includes/import.php';
require_once __DIR__ . '/includes/helpers.php';


use Monolog\Level;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

function custom_import_endpoint_handler(WP_REST_Request $request)
{

    $log = new Logger('data-sync');
    $log->pushHandler(new StreamHandler(__DIR__ . '/logs/sync.log', Level::Info));

    $log->info('Request received at ' . date('Y-m-d H:i:s'));
    $payload = $request->get_param('data');

    if (empty($payload) || !is_array($payload)) {
        // Log error if payload is empty or not an array
        $log->error('Invalid or empty payload received.');
        return new WP_REST_Response(['success' => false, 'message' => 'Invalid or empty payload.'], 400);
    }


    foreach ($payload as $betting_site_data) {

        if (!empty($betting_site_data['Name'])) {
            $post_id = primary_import1($betting_site_data);
        }
        if (!empty($betting_site_data['Affiliate Link'])) {
            secondary_import1($betting_site_data, $post_id);
        }
    }

    return new WP_REST_Response(['success' => true], 200);

}





// Register the REST API route
function custom_import_register_routes()
{
    register_rest_route('central-dashboard/v1', '/sync', array(
        'methods' => 'POST',
        'callback' => 'custom_import_endpoint_handler',
        'permission_callback' => '__return_true',
    ));

}
add_action('rest_api_init', 'custom_import_register_routes');
