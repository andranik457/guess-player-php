<?php

/**
 * Class CElasticSearch
 */
class CElasticGuessPlayer extends CModelElasticGuessPlayer {

    /**
     * @param $indexName
     * @param array $body
     * @return array
     */
    public function createIndex($indexName, $body = []) {
        $params = [
            'index' => $indexName
        ];

        if (count($body) > 0) {
            $params['body'] = $body;
        }

        $response = $this->client->indices()->create($params);

        return $response;
    }

    /**
     * @param $params
     * @return array
     */
    public function bulk($params) {
        $response = $this->client->bulk($params);

        return $response;
    }

    /**
     * @param $params
     * @return array
     */
    public function search($params) {
        $response = $this->client->search($params);

        return $response;
    }

    /**
     * @param $params
     * @return array
     */
    public function count($params) {
        $response = $this->client->count($params);

        return $response;
    }
}