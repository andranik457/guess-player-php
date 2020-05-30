<?php

use Elasticsearch\ClientBuilder;

class CModelElasticGuessPlayer {

    /**
     * @var
     */
    private $host;

    /**
     * @var
     */
    private $port;

    /**
     * @var
     */
    private $username;

    /**
     * @var
     */
    private $password;

    /**
     * @var \Elasticsearch\Client
     */
    protected $client;

    /**
     * CModelElasticSearch constructor.
     */
    function __construct() {
        $this->host     = ELASTIC_GUESS_PLAYER_CONFIG['host'];
        $this->port     = ELASTIC_GUESS_PLAYER_CONFIG['port'];
        $this->username = ELASTIC_GUESS_PLAYER_CONFIG['username'];
        $this->password = ELASTIC_GUESS_PLAYER_CONFIG['password'];

        $params = [
            [
                'host' => $this->host,
                'port' => $this->port,
                'user' => $this->username,
                'pass' => $this->password
            ]
        ];

        try {
            $this->client = ClientBuilder::create()->setHosts($params)->build();

        } catch (Exception $e) {
            echo 'Cant connect to ElasticSearch: ',  $e->getMessage(), "\n";
        }

    }

    /**
     *
     */
    public function __destruct() {
        $this->client = null;
    }


}