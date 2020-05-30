<?php
{
    # PHP ini
    ini_set('memory_limit', '6144M');

    $hostname = gethostname();
    if ($hostname === 'trebel-HP-Notebook') {
        # Show all errors and notices
        ini_set('display_errors', 1); ini_set('display_startup_errors', 1); error_reporting(E_ALL);

        # DIR
        define('DIR_ROOT',      '/var/www/guess-player-php/');
        define('DIR_LIBRARY',   '/var/www/guess-player-php/library/');
        define('DIR_INCLUDE',   '/var/www/guess-player-php/include/');
        define('DIR_TMP',       '/tmp/');

        # mongo guess player
        $mongoGuessPLayerConfig = [
            'host'      => "mongodb://localhost:27017",
            'params'    => ''
        ];

        # elastic guess player
        $elasticGuessPLayerConfig = [
            'host'      => 'localhost',
            'port'      => '9200',
            'username'  => '',
            'password'  => ''
        ];
    }
    else {
        # DIR
        define('DIR_ROOT',      '/var/www/guess-player-php/');
        define('DIR_LIBRARY',   '/var/www/guess-player-php/library/');
        define('DIR_INCLUDE',   '/var/www/guess-player-php/include/');
        define('DIR_TMP',       '/tmp/');

        # mongo guess player
        $mongoGuessPLayerConfig = [
            'host'      => "mongodb://localhost:27017",
            'params'    => ''
        ];

        # elastic guess player
        $elasticGuessPLayerConfig = [
            'host'      => 'localhost',
            'port'      => '9200',
            'username'  => '',
            'password'  => ''
        ];
    }

    # mongo
    define('MONGO_GUESS_PLAYER_CONFIG',     $mongoGuessPLayerConfig);

    # elastic
    define('ELASTIC_GUESS_PLAYER_CONFIG',   $elasticGuessPLayerConfig);


    # require databases
    require_once(DIR_INCLUDE . 'catalog/databases.inc.php');

    # Helpful defines
    require_once(DIR_INCLUDE . 'catalog/helpful.inc.php');

    # For mongoDB php7.* connection
    require_once(DIR_LIBRARY . 'mongo/vendor/autoload.php');

    # For elasticSearch
    require_once(DIR_LIBRARY . 'elasticsearch/vendor/autoload.php');

    # defined collections
    require_once(DIR_INCLUDE . 'catalog/collections.inc.php');

    # defined elastic settings
    require_once(DIR_INCLUDE . 'catalog/elastic-settings.inc.php');

    # AutoLoad class
    require_once(DIR_INCLUDE . 'catalog/autoload.inc.php');

}