<?php
declare(strict_types=1);
namespace Toumoro\TmMasterslave\Utility;


/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use Doctrine\Common\EventManager;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\Statement;
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\DBAL\Event\ConnectionEventArgs;
use Doctrine\DBAL\Events;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;




/**
 * Description of Connection
 *
 * @author simouel
 */
class Connection extends \TYPO3\CMS\Core\Database\Connection {

    /**
     * Master and slave connection (one of the randomly picked slaves).
     *
     * @var \Doctrine\DBAL\Driver\Connection[]
     */
    protected $connections = array('master' => null, 'slave' => null);

    /**
     * You can keep the slave connection and then switch back to it
     * during the request if you know what you are doing.
     *
     * @var boolean
     */
    protected $keepSlave = false;


    /**
     * Initializes a new instance of the Connection class.
     *
     * @param array $params The connection parameters.
     * @param Driver $driver The driver to use.
     * @param Configuration|null $config The configuration, optional.
     * @param EventManager|null $em The event manager, optional.
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    public function __construct(array $params, Driver $driver, Configuration $config = null, EventManager $em = null)
    {
        if ( !isset($params['slaves']) || !isset($params['master'])) {
            throw new \InvalidArgumentException('master or slaves configuration missing');
        }
        if (count($params['slaves']) == 0) {
            throw new \InvalidArgumentException('You have to configure at least one slaves.');
        }

        $params['master']['driver'] = $params['driver'];
        foreach ($params['slaves'] as $slaveKey => $slave) {
            $params['slaves'][$slaveKey]['driver'] = $params['driver'];
        }
        $this->keepSlave = isset($params['keepSlave']) ? (bool) $params['keepSlave'] : false;

        parent::__construct($params, $driver, $config, $eventManager);
    }

    /**
     * Checks if the connection is currently towards the master or not.
     *
     * @return boolean
     */
    public function isConnectedToMaster()
    {
        return $this->_conn !== null && $this->_conn === $this->connections['master'];
    }

    /**
     * {@inheritDoc}
     */
    public function connect($connectionName = null,$deb = false) : bool
    {

        $requestedConnectionChange = ($connectionName !== null);
        $connectionName            = $connectionName ?: 'slave';


        if ($connectionName !== 'slave' && $connectionName !== 'master') {
            throw new \InvalidArgumentException("Invalid option to connect(), only master or slave allowed.");
        }

        // If we have a connection open, and this is not an explicit connection
        // change request, then abort right here, because we are already done.
        // This prevents writes to the slave in case of "keepSlave" option enabled.
        if ($this->_conn && !$requestedConnectionChange) {
            return false;
        }


        $forceMasterAsSlave = false;

        if ($this->getTransactionNestingLevel() > 0) {
            $connectionName     = 'master';
            $forceMasterAsSlave = true;
        }

        if ($this->connections[$connectionName]) {
            $this->_conn = $this->connections[$connectionName];

            if ($forceMasterAsSlave && ! $this->keepSlave) {
                $this->connections['slave'] = $this->_conn;
            }

            return false;
        }

        if ($connectionName === 'master') {
            // Set slave connection to master to avoid invalid reads
            if ($this->connections['slave'] && ! $this->keepSlave) {
                unset($this->connections['slave']);
            }

            $this->connections['master'] = $this->_conn = $this->connectTo($connectionName);

            if ( ! $this->keepSlave) {
                $this->connections['slave'] = $this->connections['master'];
            }
        } else {
            $this->connections['slave'] = $this->_conn = $this->connectTo($connectionName);

        }

        if ($this->_eventManager->hasListeners(Events::postConnect)) {
            $eventArgs = new ConnectionEventArgs($this);
            $this->_eventManager->dispatchEvent(Events::postConnect, $eventArgs);
        }

        $this->_expr = GeneralUtility::makeInstance(ExpressionBuilder::class, $this);

        return true;
    }

    /**
     * Connects to a specific connection.
     *
     * @param string $connectionName
     *
     * @return \Doctrine\DBAL\Driver
     */
    protected function connectTo($connectionName)
    {
        $params = $this->getParams();

        $driverOptions = isset($params['driverOptions']) ? $params['driverOptions'] : array();

        $connectionParams = $this->chooseConnectionConfiguration($connectionName, $params);

        $user = isset($connectionParams['user']) ? $connectionParams['user'] : null;
        $password = isset($connectionParams['password']) ? $connectionParams['password'] : null;
        return $this->_driver->connect($connectionParams, $user, $password, $driverOptions);
    }

    /**
     * @param string $connectionName
     * @param array  $params
     *
     * @return mixed
     */
    protected function chooseConnectionConfiguration($connectionName, $params)
    {
        if ($connectionName === 'master') {
            return $params['master'];
        }

        return $params['slaves'][array_rand($params['slaves'])];
    }

    /**
     * {@inheritDoc}
     */
    public function executeUpdate($query, array $params = array(), array $types = array())
    {
        $this->connect('master');

        return parent::executeUpdate($query, $params, $types);
    }

    /**
     * {@inheritDoc}
     */
    public function beginTransaction()
    {
        $this->connect('master');

        parent::beginTransaction();
    }

    /**
     * {@inheritDoc}
     */
    public function commit()
    {
        $this->connect('master');

        parent::commit();
    }

    /**
     * {@inheritDoc}
     */
    public function rollBack()
    {
        $this->connect('master');

        return parent::rollBack();
    }

    /**
     * {@inheritDoc}
     */
    public function delete($tableName, array $identifier, array $types = array()): int
    {
        $this->connect('master');

        return parent::delete($tableName, $identifier, $types);
    }

    /**
     * {@inheritDoc}
     */
    public function close()
    {
        unset($this->connections['master']);
        unset($this->connections['slave']);

        parent::close();

        $this->_conn = null;
        $this->connections = array('master' => null, 'slave' => null);
    }

    /**
     * {@inheritDoc}
     */
    public function update($tableName, array $data, array $identifier, array $types = array()): int
    {
        $this->connect('master');

        return parent::update($tableName, $data, $identifier, $types);
    }

    /**
     * {@inheritDoc}
     */
    public function insert($tableName, array $data, array $types = array()): int
    {
        $this->connect('master');

        return parent::insert($tableName, $data, $types);
    }

    /**
     * {@inheritDoc}
     */
    public function exec($statement)
    {
        $this->connect('master');

        return parent::exec($statement);
    }

    /**
     * {@inheritDoc}
     */
    public function createSavepoint($savepoint)
    {
        $this->connect('master');

        parent::createSavepoint($savepoint);
    }

    /**
     * {@inheritDoc}
     */
    public function releaseSavepoint($savepoint)
    {
        $this->connect('master');

        parent::releaseSavepoint($savepoint);
    }

    /**
     * {@inheritDoc}
     */
    public function rollbackSavepoint($savepoint)
    {
        $this->connect('master');

        parent::rollbackSavepoint($savepoint);
    }

    /**
     * {@inheritDoc}
     */
    public function query()
    {

        $this->connect('master');
        $args = func_get_args();

        $logger = $this->getConfiguration()->getSQLLogger();
        if ($logger) {
            $logger->startQuery($args[0]);
        }
        $statement = call_user_func_array(array($this->_conn, 'query'), $args);

        if ($logger) {
            $logger->stopQuery();
        }

        return $statement;
    }


    /**
     * {@inheritDoc}
     */
    public function prepare($statement)
    {
        $this->connect('master');

        return parent::prepare($statement);
    }

    public function executeQuery($query, array $params = array(), $types = array(), \Doctrine\DBAL\Cache\QueryCacheProfile $qcp = null)
    {
        if ((stripos($query,"INSERT")!==FALSE) || (stripos($query,"UPDATE")!==FALSE) || (stripos($query,"DELETE")!==FALSE) || (stripos($query,"TRUNCATE")!==FALSE)) {
            $this->connect('master');
        } else {
            $this->connect('slave',true);
        }

        return parent::executeQuery($query, $params, $types, $qcp);
    }
}
