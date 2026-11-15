<?php

// Widgets plugin. Positions, styles and per-widget switches live in config/manialinks.php
// (the owner's records_eyepiece.xml); this file holds branding, images, placements and texts.

return [
    // Shown in the window footer, the logo widgets and the help window.
    'branding' => [
        'name' => env('WIDGETS_BRAND', 'Funseco'),
        'footer' => env('WIDGETS_FOOTER', 'FUNSECO'),
        // Link of the logo widgets and the help title (empty: not clickable).
        'url' => env('WIDGETS_BRAND_URL', ''),
        // Logo images (race logo, score logo). Without an image the name is written instead.
        'logo' => [
            'normal' => env('WIDGETS_LOGO_URL'),
            'focus' => env('WIDGETS_LOGO_FOCUS_URL'),
        ],
        // Two-line caption of the score logo.
        'caption' => ['TM', 'FUNSECO'],
        // Image beside the help and welcome texts (optional).
        'image' => [
            'normal' => env('WIDGETS_HELP_IMAGE_URL'),
            'focus' => env('WIDGETS_HELP_IMAGE_FOCUS_URL'),
            'link' => env('WIDGETS_HELP_IMAGE_LINK'),
        ],
        // Help window page 3.
        'about' => [
            '$FF0Funseco',
            '',
            'A TrackMania Nations Forever server controller.',
            '',
            'The widgets on your screen show the current and next track, the time, the',
            'server\'s status and your ranking. Click a widget with a corner arrow to open',
            'more details. Press F7 to hide or show the record widgets.',
        ],
    ],

    // Widget images. Each is <base_url><file>; with no base URL set the widgets fall back to
    // built-in game styles (or leave the image out). The original eyepiece images can be used
    // with WIDGETS_IMAGES_URL=http://static.undef.name/ingame/records-eyepiece/
    'images' => [
        'base_url' => env('WIDGETS_IMAGES_URL'),
        'files' => [
            'widget_open_left' => 'edge-open-ld-dark.png',
            'widget_open_right' => 'edge-open-rd-dark.png',
            'widget_plus_normal' => 'button-plus-normal.png',
            'widget_plus_focus' => 'button-plus-focus.png',
            'widget_minus_normal' => 'button-minus-normal.png',
            'widget_minus_focus' => 'button-minus-focus.png',
            'widget_ok_normal' => 'button-ok-normal.png',
            'widget_ok_focus' => 'button-ok-focus.png',
            'tmx_logo_normal' => 'logo-tmx-normal.png',
            'tmx_logo_focus' => 'logo-tmx-focus.png',
            'worldmap' => 'worldmap-pure.png',
            'no_screenshot' => 'no-screenshot-tmf.jpg',
            'progress_indicator' => 'progress_bar_yellow_2D.bik',
            // env-<name>-<enabled|focus|disabled>.png and mood-<name>-<normal|focus|disabled>.png
            'environment' => 'env-%s-%s.png',
            'mood' => 'mood-%s-%s.png',
        ],
    ],

    // Image preloading: hidden manialinks that make the client cache the images (11 batches).
    'preload' => ['enabled' => true, 'first' => 15, 'every' => 5],

    // Free widgets (records_eyepiece <placement>). display: always, race, score or a gamemode
    // (rounds, time_attack, team, laps, stunts, cup). Link priority: url, manialink, action_id,
    // chat_command. Switched on/off with manialinks.placement_widget.enabled.
    'placements' => [
        [
            'enabled' => true,
            'description' => 'Discord',
            'display' => 'race',
            'pos_x' => 44.3,   // x of the fifth top-right button (clock, manialinks.clock_widget.pos_x)
            'pos_y' => 32.5,
            'width' => 4.42,
            'height' => 6.35,
            // image, imagefocus and url come from config/funseco.php 'discord' (env: DISCORD_ICON_URL, DISCORD_ICON_FOCUS_URL, DISCORD_URL).
            'source' => 'funseco.discord',
            'chat_command' => '/discord',
        ],
    ],

    // The "add to favorites" widget needs the dedicated server's own login (not the XMLRPC login);
    // it stays hidden while no login is set.
    'favorite' => [
        'login' => env('WIDGETS_SERVER_LOGIN'),
        'name' => null,                       // null: the server name
        'zone' => env('WIDGETS_SERVER_ZONE', ''),
    ],

    // Home page linked from the TMX info window's logo.
    'tmx_home_url' => 'https://tmnf.exchange/',

    // Local record ranks above this count as "no rank" in the track list (owner: 50).
    'max_records' => 50,

    // Seconds between clock updates during the race.
    'clock_interval' => 60,

    // Rounds/cup points when neither the rounds feature nor the server has any.
    'default_rounds_points' => [10, 6, 4, 3, 2, 1],

    // Flag files that are named differently from the nation code.
    'flag_fixes' => ['SCG' => 'SRB', 'ROM' => 'ROU', 'CAR' => 'CMR'],

    'messages' => [
        'join' => '{#server}>> {#message}{title}: {#highlite}{nickname}$Z$S{#message} Zone: {#highlite}{zone}{#message} Visits: {#highlite}{visits}{#message} Ladder: {#highlite}{ladderrank}{#message} Rank: {#highlite}{serverrank}{#message}',
        'leave' => '{#server}>> {#highlite}{nickname}$Z$S{#message} from {#highlite}{nation}{#message} has left the game after {#highlite}{playtime}{#message} playtime.',
        'new_player' => 'New Player',
        'eyeset_reload' => '{#admin}>> Reload of the widget configuration done.',
        'eyeset_reload_score' => '{#admin}>> Can not reload the configuration at Score!',
        'eyeset_lfresh' => '{#admin}>> Set <refresh_interval> (normal mode) to "{1}" sec.',
        'eyeset_hfresh' => '{#admin}>> Set <refresh_interval> (nice mode) to "{1}" sec.',
        'eyeset_llimit' => '{#admin}>> Set <lower_limit> (nice mode) to "{1}" Players.',
        'eyeset_ulimit' => '{#admin}>> Set <upper_limit> (nice mode) to "{1}" Players.',
        'eyeset_forcenice' => '{#admin}>> Set <nicemode><force> to "{1}".',
        'eyeset_playermarker' => '{#admin}>> Set <features><mark_online_player_records> to "{1}".',
        'eyeset_unknown' => '{#admin}>> Did not found any possible parameter to set!',
        'payouts_disabled' => '{#server}>> WinningPayoutWidget is not enabled, no payouts to do!',
    ],
];
