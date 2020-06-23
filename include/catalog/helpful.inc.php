<?php

/**
 * helpful defines
 */

{
    # define time zone
    date_default_timezone_set('UTC');
    $now = date("Y-m-d H:i:s");

    define('MONGO_FIRST_ID',    '4bae63c00000000000000000');         # mongo first id
    define('STEP',              86400);         # One day in seconds
    define('WEEK',              604800);        # One WEEK in seconds
    define('DAY',               86400);         # One day in seconds
    define('HOUR',              3600);          # One hour in seconds
    define('MINUTE',            60);            # One minute in seconds
    define('MINUTE5',           300);           # 5 minute in seconds
    define('MINUTE15',          900);           # 15 minute in seconds
    define('SMALL_CHUNK',       1000);          # small chunk size for mongo call
    define('DEFAULT_CHUNK',     10000);         # default chunk size for mongo call
    define('LARGE_CHUNK',       100000);        # large chunk size for mongo call
    define('COLOR_RED',         "\033[31m");    # bash red
    define('COLOR_GREEN',       "\033[32m");    # bash green
    define('COLOR_YELLOW',      "\033[33m");    # bash yellow
    define('COLOR_BLUE',        "\033[34m");    # bash blue
    define('COLOR_PURPLE',      "\033[35m");    # bash purple
    define('COLOR_LIGHT_GRAY',  "\033[36m");    # bash light gray
    define('COLOR_END',         "\033[0m");     # bash color END
    define('CIRCLE',            "● ");          # circle
    define('CHECK_MARK',        "✔ ");          # check mark
    define('UN_CHECK_MARK',     "✘ ");          # unCheck mark
    define('RESOURCE_ICON',     "resource/icon/");

}