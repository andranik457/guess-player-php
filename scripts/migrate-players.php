<?php
{
    /**
     * Migrate players from mongo to elastic
     */

    # include conf file
    require_once(dirname(__FILE__) . '/../include/config.inc.php');

    $playersCursor = getPlayers();

    updateElasticPlayers($playersCursor);

}

function getPlayers() {
    $guessPlayer = new CGuessPlayer();

    $cursor = $guessPlayer->find('players', []);

    return $cursor;
}


function updateElasticPlayers($playersCursor) {
    $elasticGuessPlayer = new CElasticGuessPlayer();

    $params = [ 'body' => [] ];
    foreach ($playersCursor as $document) {
        $params['body'][] = [
            'index' => [
                '_index' => ELASTIC_GUESS_PLAYER,
                '_id'    => (string)$document['_id']
            ]
        ];

        $params['body'][] = [
            'baseName'         => $document['full_name'],
            'showName'         => $document['full_name']
        ];
    }

    $docsCount = count($params['body']);
    if ($docsCount > 0) {
        echo COLOR_BLUE, CIRCLE, 'UsersInSegment: ', $docsCount / 2, COLOR_END, "\n";

        try {
            $responses = $elasticGuessPlayer->bulk($params);
            echo COLOR_GREEN, CHECK_MARK, 'UpdateResult ', json_encode([
                'took'          => $responses['took'],
                'errors'        => $responses['errors'],
                'itemsCount'    => count($responses['items']),
            ]), COLOR_END, "\n";

        } catch (Exception $e) {
            echo 'Caught exception: ',  $e->getMessage(), "\n";
        }
    }
    else {
        echo "There is no users for upsert! \n";
    }
}