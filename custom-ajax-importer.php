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
        } elseif (! empty($betting_site_data['Affiliate link'])) {

            dd('secondary_import');

            secondary_import($betting_site_data);
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


function primary_import(array $betting_site_data): void
{
    $BettingSite_slug = 'wecptbs';
    $betting_site_slug = sanitize_title($betting_site_data['Name']);
    $existing_post     = get_page_by_path($betting_site_slug, OBJECT, $BettingSite_slug);
    $existing_post_id  = empty($existing_post) ? 0 : $existing_post->ID;
    $post_args         = array(
        'ID'          => $existing_post_id,
        'post_title'  => $betting_site_data['Name'],
        'post_status' => 'draft',
        'post_type'   => $BettingSite_slug,
    );

    $post_id           = wp_insert_post($post_args);
    if (empty($existing_post_id)) {
        $post_args['ID'] = $post_id;
        wp_update_post($post_args);
    }
    $post = get_post($post_id);

    $betting_site = new BettingSite($post);
    $betting_site->set_available_countries();
    $betting_site->set_display_order();
    dd($betting_site);
    if (! empty($betting_site_data['Image'])) {
        $betting_site->set_post_thumbnail($betting_site_data['Image']);
    }
    if (! empty($betting_site_data['Style'])) {
        $betting_site_logo_bg_color = trim(str_replace("background-color:", "", $betting_site_data['Style']));
        $betting_site->set_logo_bg_color($betting_site_logo_bg_color);
    }

    if (! empty($betting_site_data['Score'])) {
        $betting_site->set_ratings($betting_site_data['Score']);
    }

    if (! empty($betting_site_data['Title'])) {
        $betting_site_review_title_parts = explode("\n", $betting_site_data['Title']);
        $betting_site->set_review_title(
            count($betting_site_review_title_parts) >= 1 ?
                $betting_site_review_title_parts[0] : ''
        );
        $betting_site->set_review_subtitle(
            count($betting_site_review_title_parts) >= 2 ?
                $betting_site_review_title_parts[1] : ''
        );
    }

    $betting_site->set_reviews($betting_site_data);

    if (! empty($betting_site_data['Detail'])) {
        $betting_site->set_text_field(MetaKeys::review_intro_key(), $betting_site_data['Detail']);
    }

    if (! empty($betting_site_data['Award'])) {
        $betting_site->set_text_field(MetaKeys::award_key(), $betting_site_data['Award']);
    }

    if (! empty($betting_site_data['Company'])) {
        $betting_site->set_text_field(MetaKeys::company_key(), $betting_site_data['Company']);
    }

    if (! empty($betting_site_data['Founded'])) {
        $betting_site->set_text_field(MetaKeys::founded_key(), $betting_site_data['Founded']);
    }

    if (! empty($betting_site_data['Website'])) {
        $betting_site->set_text_field(MetaKeys::website_key(), $betting_site_data['Website']);
    }

    if (! empty($betting_site_data['Email'])) {
        $betting_site->set_text_field(MetaKeys::email_key(), $betting_site_data['Email']);
    }

    if (! empty($betting_site_data['Phone'])) {
        $betting_site->set_text_field(MetaKeys::phone_key(), $betting_site_data['Phone']);
    }

    if (! empty($betting_site_data['Withdrawal'])) {
        $betting_site->set_text_field(MetaKeys::withdrawal_key(), $betting_site_data['Withdrawal']);
    }

    if (! empty($betting_site_data['Verified By'])) {
        $betting_site->set_text_field(MetaKeys::verified_by_key(), $betting_site_data['Verified By']);
    }

    if (! empty($betting_site_data['Transaction Speed'])) {
        $betting_site->set_text_field(MetaKeys::transaction_speed_key(), $betting_site_data['Transaction Speed']);
    }

    if (! empty($betting_site_data['Safety Score'])) {
        $betting_site->set_text_field(MetaKeys::safety_score_key(), $betting_site_data['Safety Score']);
    }

    if (! empty($betting_site_data['Number Of Slots'])) {
        $betting_site->set_text_field(MetaKeys::slots_key(), $betting_site_data['Number Of Slots']);
    }

    if (! empty($betting_site_data['License'])) {
        $betting_site->set_license($betting_site_data['License']);
    }

    if (! empty($betting_site_data['Available Languages'])) {
        $betting_site->set_available_languages($betting_site_data['Available Languages']);
    }

    if (! empty($betting_site_data['Customer Support'])) {
        $betting_site->set_customer_support($betting_site_data['Customer Support']);
    }

    if (! empty($betting_site_data['Software Providers'])) {
        $betting_site->set_software_providers($betting_site_data['Software Providers']);
    }

    if (! empty($betting_site_data['Payement Method'])) {
        $betting_site->set_payment_providers($betting_site_data['Payement Method']);
    }

    if (! empty($betting_site_data ['Table Of Payement'])) {
        $betting_site->set_payment_methods($betting_site_data['Table Of Payement']);
    }

    $available_categories = array();
    if (! empty($betting_site_data['Casino'])) {
        $available_categories[] = 'Casino';
    }
    if (! empty($betting_site_data['Sport'])) {
        $available_categories[] = 'Sports';
    }
    if (! empty($betting_site_data['Esports'])) {
        $available_categories[] = 'Esports';
    }
    if (! empty($available_categories)) {
        $betting_site->set_association(
            $available_categories,
            Taxonomy::$betting_site_category_slug,
            MetaKeys::categories_key()
        );
    }

    if (! empty($betting_site_data['Available Currency'])) {
        $currencies = explode(",", $betting_site_data['Available Currency']);
        $currencies = array_filter($currencies, function ($item) {
            return ! empty($item);
        });
        $currencies = array_values($currencies);
        $betting_site->set_association(
            $currencies,
            Taxonomy::$betting_site_crypto_currency_slug,
            MetaKeys::crypto_key()
        );
    }

    if (! empty($betting_site_data['Jeu'])) {
        $esport_games = explode(",", $betting_site_data['Jeu']);
        $esport_games = array_filter($esport_games, function ($item) {
            return ! empty($item);
        });
        $esport_games = array_values($esport_games);
        $esport_games = array_map(function ($item) {
            return trim(str_replace("Betting Sites", "", $item));
        }, $esport_games);
        $betting_site->set_association(
            $esport_games,
            Taxonomy::$betting_site_esport_game_slug,
            MetaKeys::esport_games_key()
        );
    }

    $betting_site->set_bonus($betting_site_data);
}
