# TYPO3 Extension tm_masterslave
This extension is a WraperClass for doctrine MasterSlaveConnection that provides balancing for reads and writes requests.

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

Testing with docker

    version: "2.2"
    services:
      db:
        image: 'bitnami/mysql:5.7'
        ports:
          - '3306'
        environment:
          - MYSQL_REPLICATION_MODE=master
          - MYSQL_REPLICATION_USER=repl_user
          - MYSQL_REPLICATION_PASSWORD=repl_password
          - MYSQL_ROOT_PASSWORD=dev
          - MYSQL_USER=dev
          - MYSQL_PASSWORD=dev
          - MYSQL_DATABASE=dev
        volumes:
          - "./mysql:/var/lib/mysql"

      mysql-slave:
        image: 'bitnami/mysql:5.7'
        ports:
          - '3306'
        depends_on:
          - db
        environment:
          - MYSQL_REPLICATION_MODE=slave
          - MYSQL_REPLICATION_USER=repl_user
          - MYSQL_REPLICATION_PASSWORD=repl_password
          - MYSQL_MASTER_HOST=db
          - MYSQL_MASTER_PORT_NUMBER=3306
          - MYSQL_MASTER_ROOT_PASSWORD=dev

      web:
        image: your_typo3_php_image
        ports:
         - "80:80"
        links:
         - "db:db"
         - "mysql-slave:dbslave"
        volumes:
         - ".:/var/www"




