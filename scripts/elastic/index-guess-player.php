<?php
{

    # include conf file
    require_once(dirname(__FILE__) . '/../../include/config.inc.php');

    $elasticGuessPlayer = new CElasticGuessPlayer();

    $body = [
        'settings' => ELASTIC_GUESS_PLAYER_SETTINGS,
        'mappings' => [
            'dynamic_templates' => [
                [
                    'string_fields' => [
                        'match'                 => '*',
                        'match_mapping_type'    => 'string',
                        'mapping'               => [
                            'index'         => 'not_analyzed',
                            'omit_norms'    => true,
                            'type'          => 'string'
                        ]
                    ]
                ]
            ],
            'properties' => [
                'baseName' => [
                    'type' => 'text'
                ],
                'showName' => [
                    'type' => 'text',
                    'analyzer'  => 'autocomplete'
                ]
            ]
        ]
    ];

    $result = $elasticGuessPlayer->createIndex(ELASTIC_GUESS_PLAYER, $body);
    var_dump($result);
}