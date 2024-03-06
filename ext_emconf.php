<?php

/***************************************************************
 * Extension Manager/Repository config file for ext: "rflipbook"
 *
 *
 * Manual updates:
 * Only the data in the array - anything else is removed by next write.
 * "version" and "dependencies" must not be touched!
 ***************************************************************/

$EM_CONF[$_EXTKEY] = array(
    'title' => 'Flipbook',
    'description' => 'Show flipbook from PDF',
    'category' => 'plugin',
    'author' => '',
    'author_email' => '',
    'author_company' => '',
    'shy' => '',
    'priority' => '',
    'module' => '',
    'state' => 'stable',
    'internal' => '',
    'uploadfolder' => '1',
    'createDirs' => '',
    'modify_tables' => '',
    'clearCacheOnLoad' => 0,
    'lockType' => '',
    'version' => '2.0.3',
    'constraints' => array(
        'depends' => array(
            'extbase' => '8.7.0-9.5.99',
            'fluid' => '8.7.0-9.5.99',
            'typo3' => '8.7.0-9.5.99',
        ),
        'conflicts' => array(),
        'suggests' => array(),
    ),
);

?>
