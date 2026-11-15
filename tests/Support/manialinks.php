<?php

$config = [
    'features' => [
        'refresh_interval' => '10',
        'mark_online_player_records' => 'true',
        'illuminate_names' => 'false',
        'sort_team' => 'true',
        'number_format' => 'english',
        'shorten_numbers' => 'false',
        'karma' => [
            'min_votes' => '30',
            'calculation_method' => 'tmkarma',
        ],
        'songlist' => [
            'sorting' => 'true',
            'force_songlist' => 'true',
        ],
        'tracklist' => [
            'sorting' => 'author',
            'force_tracklist' => 'true',
        ],
    ],
    'show_progress_indicator' => [
        'tracklist' => '120',
    ],
    'line_height' => 1.8,
    'positions' => [
        'left' => [
            'icon' => [
                'x' => 0.6,
                'y' => 0,
            ],
            'title' => [
                'x' => 3.2,
                'y' => -0.55,
                'halign' => 'left',
            ],
            'image_open' => [
                'x' => -0.3,
            ],
        ],
        'right' => [
            'icon' => [
                'x' => 12.5,
                'y' => 0,
            ],
            'title' => [
                'x' => 12.4,
                'y' => -0.55,
                'halign' => 'right',
            ],
            'image_open' => [
                'x' => 12.2,
            ],
        ],
    ],
    'style' => [
        'widget_race' => [
            'background_style' => 'Bgs1InRace',
            'background_substyle' => 'NavButton',
            'title_style' => 'BgsPlayerCard',
            'title_substyle' => 'BgRacePlayerName',
            'top_style' => 'BgsPlayerCard',
            'top_substyle' => 'BgCardSystem',
            'highlite_self_style' => 'BgsPlayerCard',
            'highlite_self_substyle' => 'BgCardSystem',
            'highlite_other_style' => 'BgsPlayerCard',
            'highlite_other_substyle' => 'BgCardSystem',
            'formatting_codes' => '',
            'colors' => [
                'default' => 'FFFF',
                'scores' => 'DDDF',
                'top' => 'FF0F',
                'better' => 'F00F',
                'worse' => 'CCCF',
                'self' => '3F5F',
                'background_rank' => 'AAA5',
                'background_score' => 'AAA3',
                'background_name' => 'AAA1',
            ],
        ],
        'widget_score' => [
            'background_style' => 'BgsPlayerCard',
            'background_substyle' => 'BgRacePlayerName',
            'title_style' => 'BgsPlayerCard',
            'title_substyle' => 'ProgressBar',
            'formatting_codes' => '',
            'colors' => [
                'default' => 'FFFF',
                'scores' => 'DDDF',
            ],
        ],
        'window' => [
            'headline_bgcolor' => '09FC',
            'headline_textcolor' => 'FFFF',
            'window_bgcolor' => '001B',
            'lightbox' => [
                'enabled' => 'false',
                'bgcolor' => '0008',
            ],
        ],
    ],
    'custom_ui' => [
        'enabled' => 'true',
        'notice' => 'true',
        'challenge_info' => 'true',
        'net_infos' => 'true',
        'chat' => 'true',
        'checkpoint_list' => 'false',  // the best-checkpoint-times panel takes its place (as in XAseco)
        'round_scores' => 'true',
        'scoretable' => 'false',   // scoreboard lists replace the native podium table
    ],
    'welcome_window' => [
        'enabled' => 'false',
        'autohide' => '10',
        'hide' => [
            'ranked_player' => 'false',
        ],
        'icon_style' => 'Icons128x128_1',
        'icon_substyle' => 'Credits',
        'message' => '$FF0Welcome {player}!{br}{br}However, we ask you to urgently comply with the rules. They serve the gaming culture on this server and are all participating players have fun together! All violators will be kicked or banned depending on the seriousness of the offense.{br}{br}We think a certain minimum level of decency can be expected of all players. These include for example, a normal tone on this server. When insults, abuse and other non appropriate behavior appropriate action being taken.{br}{br}Play fair on this server! Behave like normal people. We think that the rules above should not be too difficult to follow. They reflect the interests of all players resist on this server.{br}{br}$w$oAnd now have fun at {server}!',
        'image' => [
            'normal' => 'http://static.undef.name/ingame/records-eyepiece/welcome-records-eyepiece-normal.jpg',
            'focus' => 'http://static.undef.name/ingame/records-eyepiece/welcome-records-eyepiece-focus.jpg',
            'link' => 'http://www.undef.name/',
        ],
    ],
    'challenge_widget' => [
        'enabled' => 'true',
        'width' => '15.5',
        'icons' => [
            'last_track' => [
                'icon_style' => 'Icons128x128_1',
                'icon_substyle' => 'NewTrack',
            ],
            'current_track' => [
                'icon_style' => 'Icons128x128_1',
                'icon_substyle' => 'Challenge',
            ],
            'next_track' => [
                'icon_style' => 'Icons128x128_1',
                'icon_substyle' => 'LoadTrack',
            ],
        ],
        'title' => [
            'last_track' => 'Last Track',
            'current_track' => 'Current Track',
            'next_track' => 'Next Track',
        ],
        'race' => [
            'pos_x' => '49.05',
            'pos_y' => '48',
        ],
        'score' => [
            'pos_x' => '45.7',
            'pos_y' => '47.5',
        ],
    ],
    'music_widget' => [
        'enabled' => 'false',
        'advertise' => 'true',
        'title' => 'Current Song',
        'width' => '15.5',
        'pos_x' => '33.45',
        'pos_y' => '48',
        'icon_style' => 'Icons64x64_1',
        'icon_substyle' => 'Music',
    ],
    'checkpointcount_widget' => [
        'enabled' => 'true',
        'text_color' => 'DDEF',
        'pos_x' => '-7.9',
        'pos_y' => '-34.1',
        'background_style' => 'Bgs1InRace',
        'background_substyle' => 'NavButton',
    ],
    // Widget layout (see Funseco\Core\SharedKernel\UI\WidgetRegistry).
    // Widgets register into containers (or straight into a region) by order and the grid
    // computes their positions. Override a region's anchor (x, y = top-left of the first
    // container) and gap globally under 'zones' or per gamemode. Per widget,
    // `<widget>.gamemode.<mode>` may set enabled, entries, order, width, height, container,
    // region, or pos_x + pos_y to pin it outside any layout.
    // A container's row width: layout.containers.<name>.max_width.
    // These values reproduce the records_eyepiece positions per gamemode.
    'layout' => [
        'zones' => [
            // 'mid_right' => ['x' => 49.2, 'y' => 17.8, 'gap' => 0],
            'top_left' => ['x' => -64.0, 'y' => 48.5],   // screen edge; -64.7 clipped the buttons
        ],
        'gamemode' => [
            'rounds' => [
                'mid_left' => ['y' => 20.65, 'gap' => -3.05],
                'mid_right' => ['y' => -10.7],
            ],
            'time_attack' => [
                // Region defaults: mid_left (-64.7, 26.65), mid_right (49.2, 17.8), gap 0.
            ],
            'team' => [
                'mid_left' => ['y' => 23.65, 'gap' => -0.05],
                'mid_right' => ['y' => -20.0],
            ],
            'laps' => [
                'mid_left' => ['y' => 23.65, 'gap' => -0.05],
                'mid_right' => ['y' => -10.4],
            ],
            'stunts' => [
                'mid_right' => ['y' => 3.4, 'gap' => 0.1],   // below the karma widget pinned at y 17.5
            ],
            'cup' => [
                'mid_left' => ['y' => 23.65, 'gap' => -0.05],
                'mid_right' => ['y' => -14.3],
            ],
        ],
    ],
    // "DEDI CP" button (opens /dedicptms): sits in the top-left `buttons` container.
    'dedi_cp_button' => [
        'gamemode' => [
            'rounds' => ['enabled' => 'true'],
            'time_attack' => ['enabled' => 'true'],
            'team' => ['enabled' => 'true'],
            'laps' => ['enabled' => 'true'],
            'stunts' => ['enabled' => 'false'],
            'cup' => ['enabled' => 'true'],
        ],
    ],
    // "LOCAL CP" button (opens /localcptms): next to the DEDI CP button. Off by default (the default layout
    // reproduces records_eyepiece); a server turns it on per gamemode in manialinks.local.php.
    'local_cp_button' => [
        'gamemode' => [
            'rounds' => ['enabled' => 'false'],
            'time_attack' => ['enabled' => 'false'],
            'team' => ['enabled' => 'false'],
            'laps' => ['enabled' => 'false'],
            'stunts' => ['enabled' => 'false'],
            'cup' => ['enabled' => 'false'],
        ],
    ],
    'dedimania_records' => [
        'title' => 'Dedimania Records',
        'width' => '15.5',
        'icon_style' => 'Icons128x128_1',
        'icon_substyle' => 'Rankings',
        'gamemode' => [
            'rounds' => [
                'enabled' => 'true',
                'entries' => '12',
                'topcount' => '3',
            ],
            'time_attack' => [
                'enabled' => 'true',
                'entries' => '16',
                'topcount' => '3',
            ],
            'team' => [
                'enabled' => 'true',
                'entries' => '12',
                'topcount' => '3',
            ],
            'laps' => [
                'enabled' => 'true',
                'entries' => '12',
                'topcount' => '3',
            ],
            'stunts' => [
                'enabled' => 'false',
            ],
            'cup' => [
                'enabled' => 'true',
                'entries' => '12',
                'topcount' => '3',
            ],
        ],
    ],
    'ultimania_records' => [
        'title' => 'Ultimania Records',
        'width' => '15.5',
        'icon_style' => 'Icons128x128_1',
        'icon_substyle' => 'Rankings',
        'gamemode' => [
            'rounds' => [
                'enabled' => 'false',
            ],
            'time_attack' => [
                'enabled' => 'false',
            ],
            'team' => [
                'enabled' => 'false',
            ],
            'laps' => [
                'enabled' => 'false',
            ],
            'stunts' => [
                'enabled' => 'false',
                'pos_x' => '-64.7',
                'pos_y' => '23.65',
                'entries' => '16',
                'topcount' => '3',
            ],
            'cup' => [
                'enabled' => 'false',
            ],
        ],
    ],
    'local_records' => [
        'title' => 'Local Records',
        'width' => '15.5',
        'icon_style' => 'Icons128x128_1',
        'icon_substyle' => 'Rankings',
        'gamemode' => [
            'rounds' => [
                'enabled' => 'true',
                'region' => 'mid_left',
                'priority' => 20,
                'entries' => '11',
                'topcount' => '3',
            ],
            'time_attack' => [
                'enabled' => 'true',
                'entries' => '14',
                'topcount' => '3',
            ],
            'team' => [
                'enabled' => 'true',
                'region' => 'mid_left',
                'priority' => 20,
                'entries' => '11',
                'topcount' => '3',
            ],
            'laps' => [
                'enabled' => 'true',
                'region' => 'mid_left',
                'priority' => 20,
                'entries' => '11',
                'topcount' => '3',
            ],
            'stunts' => [
                'enabled' => 'true',
                'entries' => '10',
                'topcount' => '3',
            ],
            'cup' => [
                'enabled' => 'true',
                'region' => 'mid_left',
                'priority' => 20,
                'entries' => '11',
                'topcount' => '3',
            ],
        ],
    ],
    'live_rankings' => [
        'title' => 'Live Rankings',
        'width' => '15.5',
        'icon_style' => 'Icons128x128_1',
        'icon_substyle' => 'Rankings',
        'gamemode' => [
            'rounds' => [
                'enabled' => 'true',
                'display_type' => 'scores',
                'format' => '{score} ({remaining})',
                'entries' => '7',
                'topcount' => '3',
            ],
            'time_attack' => [
                'enabled' => 'true',
                'entries' => '7',
                'topcount' => '3',
            ],
            'team' => [
                'enabled' => 'true',
                'entries' => '2',
                'topcount' => '2',
            ],
            'laps' => [
                'enabled' => 'true',
                'display_type' => 'checkpoints',
                'entries' => '7',
                'topcount' => '3',
            ],
            'stunts' => [
                'enabled' => 'true',
                'entries' => '3',
                'topcount' => '3',
            ],
            'cup' => [
                'enabled' => 'true',
                'entries' => '5',
                'topcount' => '3',
            ],
        ],
    ],
    'round_score' => [
        'title' => 'Round Score',
        'width' => '15.5',
        'race' => [
            'icon_style' => 'Icons64x64_1',
            'icon_substyle' => 'RestartRace',
        ],
        'warmup' => [
            'icon_style' => 'BgRaceScore2',
            'icon_substyle' => 'Warmup',
        ],
        'gamemode' => [
            'rounds' => [
                'enabled' => 'true',
                'race' => [
                    'pos_x' => '49.2',
                    'pos_y' => '17.8',
                    'entries' => '14',
                    'topcount' => '3',
                ],
                'warmup' => [
                    'pos_x' => '49.2',
                    'pos_y' => '10.7',
                    'entries' => '10',
                    'topcount' => '3',
                ],
            ],
            'time_attack' => [
                'enabled' => 'false',
            ],
            'team' => [
                'enabled' => 'true',
                'race' => [
                    'pos_x' => '49.2',
                    'pos_y' => '17.8',
                    'entries' => '20',
                    'topcount' => '3',
                ],
                'warmup' => [
                    'pos_x' => '49.2',
                    'pos_y' => '10.7',
                    'entries' => '16',
                    'topcount' => '3',
                ],
            ],
            'laps' => [
                'enabled' => 'true',
                'race' => [
                    'pos_x' => '49.2',
                    'pos_y' => '16.6',
                    'entries' => '13',
                    'topcount' => '3',
                ],
                'warmup' => [
                    'pos_x' => '49.2',
                    'pos_y' => '11',
                    'entries' => '10',
                    'topcount' => '3',
                ],
            ],
            'stunts' => [
                'enabled' => 'false',
            ],
            'cup' => [
                'enabled' => 'true',
                'race' => [
                    'pos_x' => '49.2',
                    'pos_y' => '17.8',
                    'entries' => '16',
                    'topcount' => '3',
                ],
                'warmup' => [
                    'pos_x' => '49.2',
                    'pos_y' => '10.7',
                    'entries' => '12',
                    'topcount' => '3',
                ],
            ],
        ],
    ],
    'join_leave_info' => [
        'enabled' => 'true',
        'messages_in_window' => 'false',
        'add_rights' => 'true',
        'join_message' => '{#server}>> {#message}{title}: {#highlite}{nickname}$Z$S{#message} Zone: {#highlite}{zone}{#message} Visits: {#highlite}{visits}{#message} Ladder: {#highlite}{ladderrank}{#message} Rank: {#highlite}{serverrank}{#message}',
        'leave_message' => '{#server}>> {#highlite}{nickname}$Z$S{#message} from {#highlite}{nation}{#message} has left the game after {#highlite}{playtime}{#message} playtime.',
    ],
    'winning_payout' => [
        'enabled' => 'false',
        'minimum_server_coppers' => '250',
        'players' => [
            'minimum_amount' => '8',
            'rank_limit' => '10000',
            'maximum_coppers' => '60',
            'reset_limit' => '86400',
        ],
        'pay_coppers' => [
            'first' => '20',
            'second' => '15',
            'third' => '10',
        ],
        'ignore' => [
            'operator' => 'false',
            'admin' => 'false',
            'masteradmin' => 'false',
        ],
        'widget' => [
            'title' => 'Finish Winners',
            'pos_x' => '37.8',
            'pos_y' => '-30.7',
            'background_style' => 'BgsPlayerCard',
            'background_substyle' => 'BgRacePlayerName',
            'title_style' => 'BgsPlayerCard',
            'title_substyle' => 'ProgressBar',
            'icon_style' => 'BgRaceScore2',
            'icon_substyle' => 'Fame',
        ],
        'colors' => [
            'won' => '5F0F',
            'coppers' => 'FF9F',
            'rank_limit' => 'F00F',
            'disconnected' => 'F00F',
            'no_tmu' => 'FF0F',
        ],
    ],
    'timelimit_clock' => [
        // FlexiTime clock: stacks in the right-hand column above the record lists.
        // Pin per gamemode with gamemode.<mode>.pos_x / pos_y if needed.
    ],
    'teamrace_select' => [
        // TeamRace team-select panel, legacy `pos` units [x, y] (negative x = right half of the screen)
        'ready' => [-0.50, -0.47],
        'spectate' => [-0.76, -0.47],
        'main' => [-0.50, 0.15],
        'spectators' => [-0.6, 0.3],
    ],
    // TeamRace results window, legacy `pos` units [x, y]
    'teamrace_results' => [0.7, 0.5],
    'message_window' => [
        // System message window, top centre (XAseco msglog window)
        'pos_x' => -49,
        'pos_y' => 43.5,
    ],
    'msglog_button' => [
        // /msglog button, bottom left just left of the chat
        'pos_x' => -63.9,
        'pos_y' => -33.5,
    ],
    // Vote, donate and admin panel templates and their positions: config/panels.php
    'donation_widget' => [
        'enabled' => 'true',
        'text_color' => 'FC0F',
        'amounts' => '100,250,500,1000,2000,2500,5000,10000',
        'public_appreciation_threshold' => '50',
        'widget' => [
            'pos_x' => '-47.9',
            'pos_y' => '18.2',
            'icon_style' => 'Icons128x128_1',
            'icon_substyle' => 'Coppers',
            'background_style' => 'BgsPlayerCard',
            'background_substyle' => 'BgRacePlayerName',
            'button_style' => 'Bgs1InRace',
            'button_substyle' => 'BgIconBorder',
            'button_color' => '000F',
        ],
    ],
    'ladderlimit_widget' => [
        'enabled' => 'true',
        'text_color' => 'FC0F',
        'roc_server' => 'true',
        'background_style' => 'BgsPlayerCard',
        'background_substyle' => 'ProgressBar',
    ],
    'gamemode_widget' => [
        'enabled' => 'true',
        'text_color' => 'FC0F',
        'background_style' => 'BgsPlayerCard',
        'background_substyle' => 'ProgressBar',
    ],
    'player_spectator_widget' => [
        'enabled' => 'true',
        'text_color' => 'FC0F',
        'background_style' => 'BgsPlayerCard',
        'background_substyle' => 'ProgressBar',
    ],
    'current_ranking_widget' => [
        'enabled' => 'true',
        'text_color' => 'FC0F',
        'background_style' => 'BgsPlayerCard',
        'background_substyle' => 'ProgressBar',
    ],
    'tmexchange_widget' => [
        'enabled' => 'true',
        'text_color' => 'FC0F',
        'pos_x' => '25.1',
        'pos_y' => '39.3',
        'background_style' => 'BgsPlayerCard',
        'background_substyle' => 'ProgressBar',
    ],
    'trackcount_widget' => [
        'enabled' => 'true',
        'text_color' => 'FC0F',
        'pos_x' => '34.7',
        'pos_y' => '39.3',
        'background_style' => 'BgsPlayerCard',
        'background_substyle' => 'ProgressBar',
    ],
    'visitors_widget' => [
        'enabled' => 'true',
        'text_color' => 'FC0F',
        'pos_x' => '39.5',
        'pos_y' => '39.3',
        'background_style' => 'BgsPlayerCard',
        'background_substyle' => 'ProgressBar',
    ],
    'clock_widget' => [
        'enabled' => 'true',
        'timeformat' => 'H:i',
        'default_timezone' => 'Europe/Berlin',
        'text_color' => 'FC0F',
        'race' => [
            'pos_x' => '44.3',
            'pos_y' => '39.3',
            'background_style' => 'BgsPlayerCard',
            'background_substyle' => 'ProgressBar',
        ],
        'score' => [
            'pos_x' => '41.05',
            'pos_y' => '33.2',
            'background_style' => 'BgsPlayerCard',
            'background_substyle' => 'BgRacePlayerName',
        ],
    ],
    'favorite_widget' => [
        'enabled' => 'true',
        'text_color' => 'FC0F',
        'race' => [
            'pos_x' => '44.3',
            'pos_y' => '32.6',
            'background_style' => 'BgsPlayerCard',
            'background_substyle' => 'ProgressBar',
        ],
        'score' => [
            'pos_x' => '-47.9',
            'pos_y' => '33.2',
            'background_style' => 'BgsPlayerCard',
            'background_substyle' => 'BgRacePlayerName',
        ],
    ],
    'next_environment_widget' => [
        'enabled' => 'true',
        'text_color' => 'FC0F',
        'pos_x' => '49.1',
        'pos_y' => '-40.9',
        'background_style' => 'BgsPlayerCard',
        'background_substyle' => 'BgRacePlayerName',
    ],
    'next_gamemode_widget' => [
        'enabled' => 'true',
        'text_color' => 'FC0F',
        'pos_x' => '53.9',
        'pos_y' => '-40.9',
        'background_style' => 'BgsPlayerCard',
        'background_substyle' => 'BgRacePlayerName',
    ],
    'eyepiece_widget' => [
        'text_color' => 'FC0F',
        'race' => [
            'enabled' => 'false',
            'pos_x' => '-62',
            'pos_y' => '47',
        ],
        'score' => [
            'enabled' => 'true',
            'pos_x' => '58.7',
            'pos_y' => '-40.9',
            'background_style' => 'BgsPlayerCard',
            'background_substyle' => 'BgRacePlayerName',
        ],
    ],
    'placement_widget' => [
        'enabled' => 'true',
        'placement' => [
            'enabled' => 'true',
            'description' => 'AFK',
            'display' => 'race',
            'pos_x' => '-62.7',
            'pos_y' => '-20',
            'width' => '3.3',
            'height' => '2.3',
            'image' => 'http://i.minus.com/i58QoKYWPBD4a.png',
            'imagefocus' => 'http://i.minus.com/i58QoKYWPBD4a.png',
            'chat_command' => '/afk',
            'url' => '',
        ],
    ],
    'nicemode' => [
        'enabled' => 'true',
        'force' => 'false',
        'refresh_interval' => '10',
        'limits' => [
            'lower_limit' => '24',
            'upper_limit' => '25',
        ],
        'colors' => [
            'top' => 'FF0F',
            'worse' => '19FF',
        ],
        'allow' => [
            'dedimania_records' => 'true',
            'ultimania_records' => 'false',
            'local_records' => 'true',
            'live_rankings' => 'true',
        ],
    ],
    'messages' => [
        'widgets_disabled' => '{#server}>> Your record widgets are now hidden, press F7 to reactivate them.',
        'widgets_enabled' => '{#server}>> Your record widgets are now shown, press F7 to hide them.',
        'widgets_preference_disabled' => '{#server}>> Your record widgets are now permanent hidden and your preference was saved, type {#highlite}/eyepiece show{#server} to reactivate them.',
        'widgets_preference_enabled' => '{#server}>> Your record widgets are now permanent shown and your preference was saved, type {#highlite}/eyepiece hide{#server} to hide them.',
        'toggling_disabled' => '{#server}>> Sorry, you can not toggle the record widgets at the moment. There are too many players on the server, so they are in static mode now.',
        'disallow_windows_at_score' => '{#server}>> Could not display this window at score! Please try again when the race is started.',
        'winning_mail_body' => '{#karma}You won several races and won {#highlite}{1} Coppers{#karma}!{br}$L[tmtp://#addfavourite={2}]Please add {3}{#karma} to favorites!$L',
    ],
    // Bonus-map banner: XAseco put it bottom right (frame 40 -44, label right-aligned).
    // Frame x = 40 - 15 (label offset in Winnings::showHud).
    'winnings_hud' => [
        'gamemode' => [
            'rounds' => ['pos_x' => '25', 'pos_y' => '-44'],
            'time_attack' => ['pos_x' => '25', 'pos_y' => '-44'],
            'team' => ['pos_x' => '25', 'pos_y' => '-44'],
            'laps' => ['pos_x' => '25', 'pos_y' => '-44'],
            'stunts' => ['pos_x' => '25', 'pos_y' => '-44'],
            'cup' => ['pos_x' => '25', 'pos_y' => '-44'],
        ],
    ],
    'images' => [
        'widget_open_left' => 'http://static.undef.name/ingame/records-eyepiece/edge-open-ld-dark.png',
        'widget_close_left' => 'http://static.undef.name/ingame/records-eyepiece/edge-close-lu-dark.png',
        'widget_open_right' => 'http://static.undef.name/ingame/records-eyepiece/edge-open-rd-dark.png',
        'widget_plus_normal' => 'http://static.undef.name/ingame/records-eyepiece/button-plus-normal.png',
        'widget_plus_focus' => 'http://static.undef.name/ingame/records-eyepiece/button-plus-focus.png',
        'widget_minus_normal' => 'http://static.undef.name/ingame/records-eyepiece/button-minus-normal.png',
        'widget_minus_focus' => 'http://static.undef.name/ingame/records-eyepiece/button-minus-focus.png',
        'widget_ok_normal' => 'http://static.undef.name/ingame/records-eyepiece/button-ok-normal.png',
        'widget_ok_focus' => 'http://static.undef.name/ingame/records-eyepiece/button-ok-focus.png',
        'tmx_logo_normal' => 'http://static.undef.name/ingame/records-eyepiece/logo-tmx-normal.png',
        'tmx_logo_focus' => 'http://static.undef.name/ingame/records-eyepiece/logo-tmx-focus.png',
        'worldmap' => 'http://static.undef.name/ingame/records-eyepiece/worldmap-pure.png',
        'no_screenshot' => 'http://static.undef.name/ingame/records-eyepiece/no-screenshot-tmf.jpg',
        'progress_indicator' => 'http://static.undef.name/ingame/records-eyepiece/progress_bar_yellow_2D.bik',
        'environment' => [
            'enabled' => [
                'icon_bay' => 'http://static.undef.name/ingame/records-eyepiece/env-bay-enabled.png',
                'icon_coast' => 'http://static.undef.name/ingame/records-eyepiece/env-coast-enabled.png',
                'icon_desert' => 'http://static.undef.name/ingame/records-eyepiece/env-desert-enabled.png',
                'icon_island' => 'http://static.undef.name/ingame/records-eyepiece/env-island-enabled.png',
                'icon_rally' => 'http://static.undef.name/ingame/records-eyepiece/env-rally-enabled.png',
                'icon_snow' => 'http://static.undef.name/ingame/records-eyepiece/env-snow-enabled.png',
                'icon_stadium' => 'http://static.undef.name/ingame/records-eyepiece/env-stadium-enabled.png',
            ],
            'focus' => [
                'icon_bay' => 'http://static.undef.name/ingame/records-eyepiece/env-bay-focus.png',
                'icon_coast' => 'http://static.undef.name/ingame/records-eyepiece/env-coast-focus.png',
                'icon_desert' => 'http://static.undef.name/ingame/records-eyepiece/env-desert-focus.png',
                'icon_island' => 'http://static.undef.name/ingame/records-eyepiece/env-island-focus.png',
                'icon_rally' => 'http://static.undef.name/ingame/records-eyepiece/env-rally-focus.png',
                'icon_snow' => 'http://static.undef.name/ingame/records-eyepiece/env-snow-focus.png',
                'icon_stadium' => 'http://static.undef.name/ingame/records-eyepiece/env-stadium-focus.png',
            ],
            'disabled' => [
                'icon_bay' => 'http://static.undef.name/ingame/records-eyepiece/env-bay-disabled.png',
                'icon_coast' => 'http://static.undef.name/ingame/records-eyepiece/env-coast-disabled.png',
                'icon_desert' => 'http://static.undef.name/ingame/records-eyepiece/env-desert-disabled.png',
                'icon_island' => 'http://static.undef.name/ingame/records-eyepiece/env-island-disabled.png',
                'icon_rally' => 'http://static.undef.name/ingame/records-eyepiece/env-rally-disabled.png',
                'icon_snow' => 'http://static.undef.name/ingame/records-eyepiece/env-snow-disabled.png',
                'icon_stadium' => 'http://static.undef.name/ingame/records-eyepiece/env-stadium-disabled.png',
            ],
        ],
        'mood' => [
            'enabled' => [
                'sunrise' => 'http://static.undef.name/ingame/records-eyepiece/mood-sunrise-normal.png',
                'day' => 'http://static.undef.name/ingame/records-eyepiece/mood-day-normal.png',
                'sunset' => 'http://static.undef.name/ingame/records-eyepiece/mood-sunset-normal.png',
                'night' => 'http://static.undef.name/ingame/records-eyepiece/mood-night-normal.png',
            ],
            'focus' => [
                'sunrise' => 'http://static.undef.name/ingame/records-eyepiece/mood-sunrise-focus.png',
                'day' => 'http://static.undef.name/ingame/records-eyepiece/mood-day-focus.png',
                'sunset' => 'http://static.undef.name/ingame/records-eyepiece/mood-sunset-focus.png',
                'night' => 'http://static.undef.name/ingame/records-eyepiece/mood-night-focus.png',
            ],
            'disabled' => [
                'sunrise' => 'http://static.undef.name/ingame/records-eyepiece/mood-sunrise-disabled.png',
                'day' => 'http://static.undef.name/ingame/records-eyepiece/mood-day-disabled.png',
                'sunset' => 'http://static.undef.name/ingame/records-eyepiece/mood-sunset-disabled.png',
                'night' => 'http://static.undef.name/ingame/records-eyepiece/mood-night-disabled.png',
            ],
        ],
    ],
];

// Per-installation overrides (not in git): config/manialinks.local.php returns a partial array, e.g.
// ['ladderlimit_widget' => ['enabled' => 'false']]. Merged over the defaults above.
$local = __DIR__.'/manialinks.local.php';

return is_file($local) ? array_replace_recursive($config, require $local) : $config;
