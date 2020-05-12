# Configuration

In this example, the configuration is done in AdditionalConfiguration.php

    $masterDbT3 = [        
            'charset' => 'utf8',        
            'dbname' => 'MASTER_DATABASE_NAME',        
            'driver' => 'mysqli',        
            'host' => 'MASTER_DATABASE_HOST',        
            'password' => 'MASTER_DATABASE_PASSWORD',        
            'user' => 'MASTER_DATABASE_USER',        
            'persistentConnection' => false,
    ];
    $slavesDbT3[0] = $masterDbT3;        
    $slavesDbT3[0]['host'] = 'SLAVE_HOST_1';        
    $GLOBALS['TYPO3_CONF_VARS']['DB'] = array(        
        'Connections' => [        
        'Default' => $masterDbT3,        
      ],
    );     
    // class que gère la master slave        
    $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['wrapperClass'] = 'Toumoro\TmMasterslave\Utility\Connection';        
    $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['master'] = $masterDbT3;        
    $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['slaves'] = $slavesDbT3;  

