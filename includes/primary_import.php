<?php

use We_M_Betting_Site as BettingSite;
use We_Cpt_Betting_Site as CptBettingSite;
use We_Cpt_Betting_Site_Meta_Keys as MetaKeys;
use We_Taxonomy as Taxonomy;

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

    $betting_site_logo_bg_color = ! empty($betting_site_data['Style']) ? trim(str_replace("background-color:", "", $betting_site_data['Style'])) : '';
    $title_and_subtitle = extract_review_title_and_subtitle($betting_site_data['Title'] ?? '');
    $available_languages = parse_available_languages($betting_site_data['Available Languages']);
    $customer_support = parse_customer_support($betting_site_data['Customer Support']);


    $key_prefix = 'websf_';
    $meta_keys = [
        'countries' => ['DEFAULT'],
        'display_order' => PHP_INT_MAX,
        'logo_bg_color' => $betting_site_logo_bg_color,
        'review_title' => $title_and_subtitle['title'],
        'review_subtitle' => $title_and_subtitle['subtitle'],
        'rev_intro' => !empty($betting_site_data['Detail']) ? $betting_site_data['Detail'] : '',
        'award' => !empty($betting_site_data['Award']) ? $betting_site_data['Award'] : '',
        'company' => !empty($betting_site_data['Company']) ? $betting_site_data['Company'] : '',
        'founded' => !empty($betting_site_data['Founded']) ? $betting_site_data['Founded'] : '',
        'website' => !empty($betting_site_data['Website']) ? $betting_site_data['Website'] : '',
        'email' => !empty($betting_site_data['Email']) ? $betting_site_data['Email'] : '',
        'phone' => !empty($betting_site_data['Phone']) ? $betting_site_data['Phone'] : '',
        'withdrawal' => !empty($betting_site_data['Withdrawal']) ? $betting_site_data['Withdrawal'] : '',
        'verified_by' => !empty($betting_site_data['Verified By']) ? $betting_site_data['Verified By'] : '',
        'transaction_speed' => !empty($betting_site_data['Transaction Speed']) ? $betting_site_data['Transaction Speed'] : '',
        'safety_score' => !empty($betting_site_data['Safety Score']) ? $betting_site_data['Safety Score'] : '',
        'slots' => !empty($betting_site_data['Number Of Slots']) ? $betting_site_data['Number Of Slots'] : '',
        'available_languages' => $available_languages,
        'customer_support' => $customer_support,
];

    // Add prefix to all meta keys
    $meta_keys = array_combine(
        array_map(fn ($key) => $key_prefix . $key, array_keys($meta_keys)),
        array_values($meta_keys)
    );

    if (! empty($betting_site_data['Image'])) {
        $betting_site->set_post_thumbnail($betting_site_data['Image']);
    }

    if (! empty($betting_site_data['Score'])) {
        $betting_site->set_ratings($betting_site_data['Score']);
    }

    $betting_site->set_reviews($betting_site_data);

    if (! empty($betting_site_data['License'])) {
        $betting_site->set_license($betting_site_data['License']);
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

    $available_categories = [];
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