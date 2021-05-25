<?php

/***************************************************************
 * Extension Manager/Repository config file for ext: "tm_masterslave"
 *
 * Auto generated by Extension Builder 2018-05-02
 *
 * Manual updates:
 * Only the data in the array - anything else is removed by next write.
 * "version" and "dependencies" must not be touched!
 ***************************************************************/

$EM_CONF[$_EXTKEY] = [
    'title' => 'Master slave WraperClass for Doctrine',
    'description' => 'This extension is a WraperClass for doctrine MasterSlaveConnection that provides balancing for reads and writes requests.',
    'category' => 'plugin',
    'author' => 'Simon Ouellet',
    'author_email' => 'simon.ouellet@toumoro.com',
    'state' => 'alpha',
    'uploadfolder' => 0,
    'createDirs' => '',
    'clearCacheOnLoad' => 0,
    'version' => '10.4.3',
    'constraints' => [
        'depends' => [
            'typo3' => '8.7.0-10.4.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
