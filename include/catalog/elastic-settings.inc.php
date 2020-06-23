<?php

/**
 * Define elastic settings
 */

{
    define('ELASTIC_GUESS_PLAYER_SETTINGS', [
        'index' => [
            'refresh_interval' => '30s',
            'number_of_shards' => '1',
            'number_of_replicas' => '1',
            'store' => [
                'type' => 'mmapfs'
            ],
            'analysis' => [
                'filter' => [
                    'autocomplete_filter' => [
                        'type' => 'edge_ngram',
                        'min_gram' => '1',
                        'max_gram' => '20'
                    ],
                    'ascii_folding' => [
                        'type' => 'asciifolding',
                        'preserve_original' => true
                    ]
                ],
                'analyzer' => [
                    'autocomplete' => [
                        'filter' => [
                            'lowercase',
                            'autocomplete_filter',
                            'ascii_folding'
                        ],
                        'type' => 'custom',
                        'tokenizer' => 'standard'
                    ],
                    'default_search' => [
                        'filter' => [
                            'lowercase',
                            'ascii_folding',
                        ],
                        'char_filter' => [
                            'my_char_filter'
                        ],
                        'tokenizer' => 'standard'
                    ]
                ],
                'char_filter' => [
                    'my_char_filter' => [
                        'type' => 'mapping',
                        'mappings' => [
                            '\u0027 => '
                        ]
                    ]
                ]
            ]
        ]
    ]);

}