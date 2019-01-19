<?php

namespace PDOWrapper\References;


use PDOWrapper\PDOWrapper;

class PDOReference extends Reference
{
    /** @var \PDO */
    private $db;

    /**
     * PDOReference constructor.
     * @param string $dbName The name of the database being accessed by the resulting PDO connection. This is used in helper methods for mssql connections.
     * @param \PDO $db The PDO instance to be used in the wrapper
     */
    public function __construct(string $dbName, \PDO $db)
    {
        parent::__construct($dbName);
        $this->db = $db;
    }

    protected function generateWrapper()
    {
        $this->pdoWrapper = new PDOWrapper($this->db, $this->dbName);
    }
}