<?php

namespace PDOWrapper\References;


use PDOWrapper\PDOWrapper;

class StringReference extends Reference
{
    /** @var string */
    private $dsn;
    /** @var string */
    private $username;
    /** @var string */
    private $password;
    /** @var array */
    private $options;

    /**
     * StringReference constructor.
     *
     * @see \PDO
     *
     * @param string $dbName The name of the database being accessed by the resulting PDO connection. This is used in helper methods for mssql connections.
     * @param string $dsn The $dsn parameter of the PDO constructor
     * @param string $username The $username parameter of the PDO constructor
     * @param string $password The $password parameter of the PDO constructor
     * @param array $options The $options parameter of the PDO constructor
     */
    public function __construct(
        string $dbName,
        string $dsn,
        string $username = '',
        string $password = '',
        array $options = [])
    {
        parent::__construct($dbName);

        $this->dsn = $dsn;
        $this->username = $username;
        $this->password = $password;
        $this->options = $options;
    }

    protected function generateWrapper()
    {
        try {
            if ($this->username && $this->password) {
                if (!empty($this->options)) {
                    $db = new \PDO(
                        $this->dsn,
                        $this->username,
                        $this->password,
                        $this->options
                    );
                } else {
                    $db = new \PDO(
                        $this->dsn,
                        $this->username,
                        $this->password
                    );
                }
            } else {
                $db = new \PDO($this->dsn);
            }

            $this->pdoWrapper = new PDOWrapper($db, $this->dbName);
        } catch (\Exception $e){
            error_log("Error while creating PDO object for wrapper");
            error_log($e->getTraceAsString());
            die();
        }
    }
}