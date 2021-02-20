<?php

namespace Hawkelele\Database;

use Exception;
use Hawkelele\Database\Connection;
use PDO;

/**
 * Table connection abstraction 
 * 
 * @version 1.0.0 
 * @author Stefano Buico <stefano.buico@gmail.com>
 */
class Table
{
    /**
     * The connection instance
     *
     * @var Connection
     */
    private $connection;

    /**
     * The selected table name
     *
     * @var string
     */
    private $table;

    /**
     * 
     * @param Connection|PDO $connection An existing connection instance
     * @param string $table The table name
     */
    public function __construct($connection, string $table)
    {
        if ($connection instanceof Connection) {
            $this->connection = $connection;
        } else if ($connection instanceof PDO) {
            $this->connection = Connection::createFromPDOInstance($connection);
        } else {
            throw new Exception("No valid connection instance was passed");
        }

        $this->table = $table;
    }

    /**
     * Executes a select statement and returns its results
     *
     * @param string $what The query string to append. Usually starts with "WHERE "
     * @param array|null $columns An array of the columns to select 
     * @param array|null $parameters An array of parameteres to bind to the statement
     * @return array The query results
     */
    public function select(string $what = "", ?array $columns = ['*'], ?array $parameters = [])
    {
        if (empty($columns)) {
            $columns = ['*'];
        }
        $fields = implode(', ', $columns);
        return $this->connection->query("SELECT {$fields} FROM {$this->table} {$what}", $parameters);
    }

    /**
     * Executes an insert statement and returns the inserted record's id
     *
     * @param array $data The data of the record to insert
     * @return int The id of the new record, or 0 if the insert failed
     */
    public function insert(array $data)
    {
        return $this->connection->getConnection()->insert($this->table, $data);
    }

    /**
     * Executes an update statement and returns the number of affected rows
     *
     * @param array $what An associative array of the columns to update
     * @param array $where An associative array of the criteria to search for updating
     * @return int The number of affected rows
     */
    public function update(array $what, array $where)
    {
        return $this->connection->getConnection()->update($this->table, $what, $where);
    }

    /**
     * Executes a delete statement and returns the number of affected rows
     *
     * @param array $where An associative array of the criteria to search for deletion
     * @return int The number of affected rows
     */
    public function delete(array $where)
    {
        return $this->connection->getConnection()->delete($this->table, $where);
    }


    /**
     * Get a list of the table's columns
     *
     * @return array
     */
    public function getColumns()
    {
        $schema = $this->connection->getConnection()->getSchemaManager();
        return $schema->listTableColumns($this->table);
    }

    /**
     * Get a list of the table's properties
     *
     * @return array
     */
    public function getProperties()
    {
        $schema = $this->connection->getConnection()->getSchemaManager();
        return [
            'details' => $schema->listTableDetails($this->table),
            'indexes' => $schema->listTableIndexes($this->table),
            'foreign_keys' => $schema->listTableForeignKeys($this->table),
        ];
    }
}
