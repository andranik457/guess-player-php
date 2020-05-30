<?php

/**
 * Class CModelMongo
 */
class CModelMongoGuessPlayer {

    /**
     * @var
     */
    private $mongoHost;

    /**
     * @var
     */
    private $mongoParams;

    /**
     * @var string
     */
    private $mongoDb = MONGO_DB_GUESS_PLAYER;

    /**
     * @var \MongoDB\Database|null
     */
    protected $db = null;

    /**
     * CModelMongo constructor.
     */
    function __construct() {
        $this->mongoHost = MONGO_GUESS_PLAYER_CONFIG['host'];
        $this->mongoParams = MONGO_GUESS_PLAYER_CONFIG['params'];

        if ($this->mongoParams != '') {
            $m = new MongoDB\Client( $this->mongoHost, $this->mongoParams );
        }
        else {
            $m = new MongoDB\Client( $this->mongoHost );
        }

        $this->db = $m->selectDatabase( $this->mongoDb );
    }

    /**
     *
     */
    public function __destruct() {
        $this->db = null;
    }


}