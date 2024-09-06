<?php

use We_M_Betting_Site as BettingSite;
use We_Cpt_Betting_Site as CptBettingSite;
use We_Cpt_Betting_Site_Meta_Keys as MetaKeys;
use We_Taxonomy as Taxonomy;

function primary_import1(array $betting_site_data): void
{
    $BettingSite_slug = 'wecptbs';
    $betting_site_slug = sanitize_title($betting_site_data['Name']);


    // Query for existing posts by path
    $existing_posts = get_posts(array(
        'title'          => trim($betting_site_data['Name']),
        'post_type'      => $BettingSite_slug,
        'post_status'    => array('publish', 'draft'), // Include draft and other statuses
        'posts_per_page' => 1, // We only need one post
    ));


    // If the post already exists, get the ID
    if (!empty($existing_posts)) {
        $post_id = $existing_posts[0]->ID; // Use the existing post ID
    } else {
        // Create a new post if it doesn't exist
        $post_args = array(
            'post_title'  => trim($betting_site_data['Name']),
            'post_status' => 'publish', // Create post as published
            'post_type'   => $BettingSite_slug,
        );
        $post_id = wp_insert_post($post_args); // Insert the new post and get its ID
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


    // Create/update meta keys for the post
    foreach ($meta_keys as $meta_key => $meta_value) {
        update_post_meta($post_id, $meta_key, $meta_value);
    }


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


function secondary_import1(array $betting_site_data): void
{

    $BettingSite_slug = 'wecptbs';

    $betting_site_slug = sanitize_title($betting_site_data['Casino/betting site']);
    $existing_post     = get_page_by_path($betting_site_slug, OBJECT, $BettingSite_slug);
    if (empty($existing_post)) {
        return;
    }

    $betting_site = new BettingSite($existing_post);
    if (! empty($betting_site_data['Affiliate link'])) {
        $link = wp_http_validate_url($betting_site_data['Affiliate link']);
        if (! empty($link)) {
            $categories = $betting_site->get_categories();
            $all_bonus  = array();
            foreach ($categories as $category) {
                $bonus = $betting_site->get_featured_bonus($category['id']);
                if (! empty($bonus)) {
                    $all_bonus[] = $bonus;
                }
            }
            if (! empty($all_bonus)) {
                foreach ($all_bonus as &$bonus) {
                    $bonus['link'] = $link;
                    $features      = array();
                    foreach ($bonus['features'] as $feature) {
                        $features[] = array( 'feature' => $feature );
                    }
                    $bonus['features'] = $features;
                }
                wp_update_post(array( 'ID' => $betting_site->post->ID, 'post_status' => 'publish' ));
                update_post_meta($betting_site->post->ID, 'websf_featured_bonus', $all_bonus);
            }
        }
    }

    if (! empty($betting_site_data['GEOs'])) {

        $all_countries        = array_keys(We_Utils::$countries);
        $available_countries  = array();
        $restricted_countries = array();

        $available_countries_string = trim(strtoupper($betting_site_data['GEOs']));
        if ($available_countries_string == 'ALL') {
            $available_countries = array_filter($all_countries, function ($country_code) {
                return ! empty($country_code) && $country_code != 'DEFAULT';
            });
            $available_countries = array_values($available_countries);
        } else {
            $available_countries = explode(",", $available_countries_string);
            $available_countries = array_map(function ($country_code) {
                return trim(strtoupper($country_code));
            }, $available_countries);
            $available_countries = array_filter($available_countries, function ($country_code) {
                return ! empty($country_code) && strlen($country_code) < 3;
            });
            $available_countries = array_values($available_countries);
        }

        if (! empty($betting_site_data['Restricted GEOs'])) {
            $restricted_countries_string = trim(strtoupper($betting_site_data['Restricted GEOs']));
            $restricted_countries        = explode(",", $restricted_countries_string);
            $restricted_countries        = array_map(function ($country_code) {
                return trim(strtoupper($country_code));
            }, $restricted_countries);
            $restricted_countries        = array_filter($restricted_countries, function ($country_code) {
                return ! empty($country_code) && strlen($country_code) < 3;
            });
            $restricted_countries        = array_values($restricted_countries);
        }

        $result = array();
        if (! empty($restricted_countries)) {
            $result = array_diff($available_countries, $restricted_countries);
        } else {
            $result = $available_countries;
        }

        update_post_meta($betting_site->post->ID, 'websf_countries', $result);
    }

    if (! empty($betting_site_data['Disclaimer'])) {
        $disclaimer = trim(htmlspecialchars_decode($betting_site_data['Disclaimer']));

        update_post_meta($betting_site->post->ID, 'websf_play_terms', $disclaimer);
    }
}
