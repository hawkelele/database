<?php

namespace Hawkelele\Database;

use Doctrine\DBAL\DriverManager;
use Exception;
use PDO;
use PDOException;


/**
 * Database connection abstraction
 *
 * @version 1.0.0 
 * @author Stefano Buico <stefano.buico@gmail.com>
 */
class Connection
{
    /**
     * The Doctrine connection instance
     *
     * @var \Doctrine\DBAL\Connection
     */
    protected $connection;

    /**
     * @param string $driver mysql|sqlite|pdo
     * @param string|null $database Name or path (if sqlite) of the database
     * @param string|null $username Connection username
     * @param string|null $password Connection password
     * @param integer|null $port Connection port
     * @param string|null $charset Connection charset
     * @param PDO|null $pdo Pre-existing instance of PDO, if already created
     * 
     * @throws Exception
     * @throws PDOException
     */
    public function __construct(string $driver, ?string $database, ?string $username = null, ?string $password = null, ?int $port = null, ?string $charset = 'UTF8', ?PDO $pdo = null)
    {
        switch ($driver) {
            case 'pdo':
                $config = [
                    'pdo' => $pdo
                ];
                break;
            case 'sqlite':
                $config = [
                    'driver' => 'pdo_sqlite',
                    'user' => $username,
                    'password' => $password,
                    'path' => $database,
                ];
                break;
            case 'mysql':
                $config = [
                    'driver' => 'pdo_mysql',
                    'user' => $username,
                    'password' => $password,
                    'dbname' => $database,
                    'port' => $port,
                    'charset' => $charset
                ];
                break;
            default:
                throw new Exception("No proper driver was specified");
        }

        try {
            $this->connection = DriverManager::getConnection($config);
        } catch (Exception $e) {
            throw new PDOException($e->getMessage(), $e->getCode(), $e->getPrevious());
        }
    }

    /**
     * Creates a new Connection instance based on a pre-existing PDO instance
     *
     * @param PDO|null $pdo Pre-existing instance of PDO
     * @return Connection
     */
    public static function createFromPDOInstance(PDO $pdo)
    {
        return new Connection('pdo', null, null, null, null, null, $pdo);
    }

    /**
     * Executes a query, returns its result, based on the guessed statement type
     *
     * @param string $statement The statement to be executed
     * @param array $parameters An array of parameters to bind to the statement
     * @return array|int Depending on the query, for select statements: 
     *                   - SELECT: an array of results
     *                   - INSERT: the id of the last inserted record as int
     *                   - UPDATE, DELETE, OTHERS: the number of affected records as int
     * @throws PDOException
     */
    public function query(string $statement, ?array $parameters = [])
    {
        $type = $this->getStatementType($statement);

        try {
            switch ($type) {
                case 'select':
                    return $this->connection->fetchAllAssociative($statement, $parameters);
                    break;
                case 'select_limit_1':
                    return $this->connection->fetchAssociative($statement, $parameters);
                    break;
                case 'insert':
                    $this->connection->executeStatement($statement, $parameters);
                    return (int) $this->connection->lastInsertId();
                    break;
                default:
                    return $this->connection->executeStatement($statement, $parameters);
            }
        } catch (Exception $e) {
            throw new PDOException($e->getMessage(), $e->getCode(), $e->getPrevious());
        }
    }

    /**
     * Guess the type of the statement based on the query string
     *
     * @param string $statement
     * @return string select|insert|other
     */
    private function getStatementType(string $statement)
    {
        // Check if SELECT
        if (preg_match('/(SELECT)+/', $statement)) {
            return 'select';
        } else if (preg_match('/(INSERT INTO)+/', $statement)) {
            return 'insert';
        }


        return 'other';
    }


    /**
     * Get current Doctrine Connection instance
     *
     * @return \Doctrine\DBAL\Connection
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * Get current PDO instance
     *
     * @return PDO
     */
    public function getPdo()
    {
        return $this->getConnection()->getWrappedConnection();
    }

    /**
     * Get a list of accessible databases
     *
     * @return array
     */
    public function getDatabases()
    {
        $schema = $this->getConnection()->getSchemaManager();
        return $schema->listDatabases();
    }

    /**
     * Get a list of existing views
     *
     * @return array
     */
    public function getViews()
    {
        $schema = $this->getConnection()->getSchemaManager();
        return $schema->listViews();
    }
}
