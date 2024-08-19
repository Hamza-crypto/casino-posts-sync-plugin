<?php

function extract_review_title_and_subtitle($title)
{

    if (empty($title)) {
        return [
            'title' => '',
            'subtitle' => ''
        ];
    }


    $title_parts = explode("\n", $title);
    $review_title = count($title_parts) >= 1 ? $title_parts[0] : '';
    $review_subtitle = count($title_parts) >= 2 ? $title_parts[1] : '';

    return [
        'title' => $review_title,
        'subtitle' => $review_subtitle
    ];
}

function parse_available_languages($language)
{
    $available_languages = array();
    $raw_value_parts     = explode(" ", $language);
    $value_parts         = array_filter($raw_value_parts, function ($item) {
        return ! empty($item);
    });
    $value_parts         = array_values($value_parts);
    foreach ($value_parts as $part) {
        $available_languages[] = strtolower($part);
    }
    if (! empty($available_languages)) {
        return $available_languages;
    }
}

function parse_customer_support(string $value)
{
    $customer_support = array();
    $raw_value_parts  = explode(",", $value);
    $value_parts      = array_filter($raw_value_parts, function ($item) {
        return ! empty($item);
    });
    $value_parts      = array_values($value_parts);
    foreach ($value_parts as $part) {
        $customer_support[] = array( 'support' => trim(htmlspecialchars_decode($part)) );
    }
    if (! empty($customer_support)) {
        return $customer_support;
    }
}
