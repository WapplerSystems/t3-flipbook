<?php

/***************************************************************
 * Extension Manager/Repository config file for ext: "flipbook"
 *
 *
 * Manual updates:
 * Only the data in the array - anything else is removed by next write.
 * "version" and "dependencies" must not be touched!
 ***************************************************************/

$EM_CONF['flipbook'] = [
    'title' => 'Flipbook',
    'description' => 'Show flipbook from PDF',
    'category' => 'plugin',
    'state' => 'stable',
    'lockType' => '',
    'version' => '12.0.1',
    'constraints' => [
        'depends' => [
            'typo3' => '12.4.0-12.4.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
