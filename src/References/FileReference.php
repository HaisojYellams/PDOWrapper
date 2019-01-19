<?php

namespace PDOWrapper\References;


use PDOWrapper\PDOWrapper;

class FileReference extends Reference
{
    /**
     * The file path to a connection file which creates a PDO instance.
     * The path must be accessible to whichever script creates the FileReference object.
     * It is recommended, though not necessary, to use an absolute rather than relative path.
     * @var string
     */
    private $filePath;

    /**
     * The name of the variable containing the PDO instance created in the file at $filePath.
     * @var string
     */
    private $pdoVariable;

    /**
     * FileReference constructor.
     * @param string $dbName The name of the database being accessed by the resulting PDO connection. This is used in helper methods for mssql connections.
     * @param string $filePath he file path to a connection file which creates a PDO instance.
     * The path must be accessible to whichever script creates the FileReference object.
     * It is recommended, though not necessary, to use an absolute rather than relative path.
     * @param string $pdoVariable The name of the variable containing the PDO instance created in the file at $filePath.
     */
    public function __construct(string $dbName, string $filePath, string $pdoVariable)
    {
        parent::__construct($dbName);
        $this->filePath = $filePath;
        $this->pdoVariable = $pdoVariable;
    }

    protected function generateWrapper()
    {
        try {
            include_once $this->filePath;

            $varName = $this->pdoVariable;
            if (isset($$varName)) {
                $pdo = $$varName;
                if (!($pdo instanceof \PDO)) {
                    throw new \Exception("Variable with name [$varName] is not an instance of PDO");
                }
                $this->pdoWrapper = new PDOWrapper($$varName, $this->dbName);
            } else {
                throw new \Exception("No variable with name [$varName] was found.");
            }
        } catch (\Exception $e) {
            error_log("Error generating PDOWrapper");
            error_log($e->getMessage());
            error_log($e->getTraceAsString());
            die();
        }
    }
}