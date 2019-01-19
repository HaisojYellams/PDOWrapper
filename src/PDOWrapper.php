<?php

namespace PDOWrapper;


class PDOWrapper
{
    /** @var \PDO */
    protected $db;
    /** @var string */
    protected $dbName;

    /**
     * PDOWrapper constructor.
     * @param \PDO $db
     * @param string $dbName
     */
    public function __construct(\PDO $db, string $dbName)
    {
        $this->db = $db;
        $this->dbName = $dbName;
    }

    /**
     * Returns the PDO instance wrapped by this class
     * @return \PDO
     */
    public function getPDO()
    {
        return $this->db;
    }

    /**
     * Pass-through to the internal PDO instance.
     *
     * @see PDO::prepare()
     *
     * @param string $statement
     * @param array $driverOptions
     * @return bool|\PDOStatement
     */
    public function prepare(string $statement, array $driverOptions = [])
    {
        return $this->db->prepare($statement, $driverOptions);
    }

    /**
     * Pass-through to the internal PDO instance.
     *
     * @see PDO::exec()
     *
     * @param string $statement
     * @return int
     */
    public function exec(string $statement)
    {
        return $this->db->exec($statement);
    }

    public static function sanitizeArray(array $array)
    {
        foreach (array_keys($array) as $key) {
            try {
                $array[$key] = htmlspecialchars($array[$key]);
            } catch (\Exception $e) {

            }
        }

        return $array;
    }

    public static function sanitize2DArray(array $array)
    {
        foreach (array_keys($array) as $key) {
            if (is_array($array[$key])) {
                try {
                    $array[$key] = static::sanitizeArray($array[$key]);
                } catch (\Exception $e) {

                }
            }
        }

        return $array;
    }

    public function execute(
        string $statement,
        array $params = [],
        array $driverOptions = []
    )
    {
        try {
            $statement = $this->db->prepare($statement, $driverOptions);
            $statement->execute($params);
            $statement->closeCursor();
        } catch (\Exception $e) {
            error_log("Error executing call");
            error_log($e->getMessage());
            error_log($e->getTraceAsString());
            die();
        }
    }

    public function fetchOne(
        string $statement,
        array $params = [],
        array $fetchOptions = [])
    {
        try {
            $driverOptions = $fetchOptions["driverOptions"] ?? [];
            $statement = $this->db->prepare($statement, $driverOptions);
            $statement->execute($params);

            $fetchType = $fetchOptions["fetchType"] ?? \PDO::FETCH_ASSOC;
            $cursorOrientation = $fetchOptions["cursorOrientation"] ?? \PDO::FETCH_ORI_NEXT;
            $cursorOffset = $fetchOptions["cursorOffset"] ?? 0;

            if (($fetchType & \PDO::FETCH_CLASS) > 0) {
                // should be fetching a class
                if (!isset($fetchOptions["class"])) {
                    throw new \Exception("Tried to fetch as a class, but no class was specified");
                }
                $constructorArguments = $fetchOptions["ctorargs"] ?? [];
                $statement->setFetchMode($fetchType, $fetchOptions["class"], $constructorArguments);
                $result = $statement->fetch(null, $cursorOrientation, $cursorOffset);
                $statement->closeCursor();

                return $result;
            } else {
                $result = $statement->fetch($fetchType, $cursorOrientation, $cursorOffset);
                $statement->closeCursor();
                if ($fetchOptions["sanitize"] ?? true) {
                    $result = static::sanitizeArray($result);
                }

                return $result;
            }
        } catch (\Exception $e) {
            error_log("Error fetching.");
            error_log($e->getMessage());
            error_log($e->getTraceAsString());
            die();
        }
    }

    public function fetchOneByOne(
        string $statement,
        array $params = [],
        array $fetchOptions = [])
    {
        try {
            $driverOptions = $fetchOptions["driverOptions"] ?? [];
            $statement = $this->db->prepare($statement, $driverOptions);
            $statement->execute($params);

            $fetchType = $fetchOptions["fetchType"] ?? \PDO::FETCH_ASSOC;
            $cursorOrientation = $fetchOptions["cursorOrientation"] ?? \PDO::FETCH_ORI_NEXT;
            $cursorOffset = $fetchOptions["cursorOffset"] ?? 0;

            if (($fetchType & \PDO::FETCH_CLASS) > 0) {
                // should be fetching a class
                if (!isset($fetchOptions["class"])) {
                    throw new \Exception("Tried to fetch as a class, but no class was specified");
                }
                $constructorArguments = $fetchOptions["ctorargs"] ?? [];
                $statement->setFetchMode($fetchType, $fetchOptions["class"], $constructorArguments);
                while ($result = $statement->fetch(null, $cursorOrientation, $cursorOffset)) {
                    yield $result;
                }
                $statement->closeCursor();
            } else {
                while (
                $result = $statement->fetch($fetchType, $cursorOrientation, $cursorOffset)
                ) {
                    if ($fetchOptions["sanitize"] ?? true) {
                        $result = static::sanitizeArray($result);
                    }

                    yield $result;
                }
                $statement->closeCursor();
            }
        } catch (\Exception $e) {
            error_log("Error fetching.");
            error_log($e->getMessage());
            error_log($e->getTraceAsString());
            die();
        }
    }

    public function fetchAll(
        string $statement,
        array $params = [],
        array $fetchOptions = [])
    {
        try {
            $driverOptions = $fetchOptions["driverOptions"] ?? [];
            $statement = $this->db->prepare($statement, $driverOptions);
            $statement->execute($params);

            $fetchType = $fetchOptions["fetchType"] ?? \PDO::FETCH_ASSOC;

            if (($fetchType & \PDO::FETCH_CLASS) > 0 || $fetchType === \PDO::FETCH_COLUMN || $fetchType === \PDO::FETCH_FUNC) {
                // should be fetching with an argument
                if (!isset($fetchOptions["fetchArgument"])) {
                    throw new \Exception("Tried to fetch but the format requires a fetchArgument");
                }
                if (($fetchType & \PDO::FETCH_CLASS) > 0) {
                    $constructorArguments = $fetchOptions["ctorargs"] ?? [];
                    $result = $statement->fetchAll($fetchType, $fetchOptions["fetchArgument"], $constructorArguments);
                } else {
                    $result = $statement->fetchAll($fetchType, $fetchOptions["fetchArgument"]);
                }
                $statement->closeCursor();

                return $result;
            } else {
                $result = $statement->fetchAll($fetchType);
                $statement->closeCursor();
                if ($fetchOptions["sanitize"] ?? true) {
                    $result = static::sanitize2DArray($result);
                }

                return $result;
            }
        } catch (\Exception $e) {
            error_log("Error fetching.");
            error_log($e->getMessage());
            error_log($e->getTraceAsString());
            die();
        }
    }
}