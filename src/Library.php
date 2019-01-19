<?php

namespace PDOWrapper;


use PDOWrapper\References\FileReference;
use PDOWrapper\References\PDOReference;
use PDOWrapper\References\Reference;
use PDOWrapper\References\StringReference;

class Library
{
    /**
     * A map where keys are names of connections and values are References.
     * References hold information needed to generate PDOWrappers, but only
     * generate those PDOWrappers when they're needed, and only one instance
     * of that connection is made. In other words, multiple attempts to get
     * the same PDOWrapper will not generate new PDOWrapper instances.
     *
     * @var array
     */
    protected static $LIBRARY = [];

    /**
     * This flag is used by the Library to determine whether or not it has
     * added the $INITIAL_REFERENCES to its internal map yet.
     * @var bool
     */
    protected static $INITIAL_REFERENCES_INITIALIZED = false;


    /**
     * Reference may be added to the library dynamically, but you may
     * want one or more reference available by default in the Library.
     *
     * Returning arrays from this method (by way of extending this class) will
     * cause the library to populate itself with the corresponding References
     * the first time any script tries to access a reference from the library.
     *
     * A method is used instead of a class property to allow child class to
     * add/remove/alter a parent's array.
     *
     * Each element in this array should be an associative array. Each of these
     * arrays must have a key of "type" and a value of "string", "file", or "pdo"
     * corresponding to whether you wish to create a StringReference, FileReference,
     * or PDOReference.
     *
     * In addition to the "type" key, each element of this array must have
     * a "name" key (with the desired UNIQUE connection name) and a key/value
     * pair corresponding to the required inputs of the corresponding Reference type.
     *
     * If any element of this array does not have the required keys, an error will be thrown when
     * the Library attempts to add the references and the script will die.
     *
     * @return array
     */
    protected static function getInitialReferences()
    {
        return [];
    }

    /**
     * Adds a Reference with the given name to the $LIBRARY.
     *
     * @param string $name
     * @param Reference $reference
     */
    private static function addReference(string $name, Reference $reference)
    {
        if (isset(static::$LIBRARY[$name])) {
            error_log("A reference with the name [$name] already exists! Skipping...");
        } else {
            static::$LIBRARY[$name] = $reference;
        }
    }

    /**
     * Attempts to add a PDOReference with the given parameters to the $LIBRARY.
     *
     * @see PDOReference
     *
     * @param string $name
     * @param string $dbName
     * @param \PDO $pdo
     */
    public static function addPDOReference(string $name, string $dbName, \PDO $pdo)
    {
        static::addReference(
            $name,
            new PDOReference($dbName, $pdo)
        );
    }

    /**
     * Attempts to add a StringReference to the $LIBRARY.
     *
     * @see StringReference
     *
     * @param string $name
     * @param string $dbName
     * @param string $dsn
     * @param string $username
     * @param string $password
     * @param array $options
     */
    public static function addStringReference(
        string $name,
        string $dbName,
        string $dsn,
        string $username = '',
        string $password = '',
        array $options = []
    )
    {
        static::addReference(
            $name,
            new StringReference(
                $dbName,
                $dsn,
                $username,
                $password,
                $options
            )
        );
    }

    /**
     * Attempts to add a FileReference to the $LIBRARY.
     *
     * @see FileReference
     *
     * @param string $name
     * @param string $dbName
     * @param string $filePath
     * @param string $pdoVariable
     */
    public static function addFileReference(
        string $name,
        string $dbName,
        string $filePath,
        string $pdoVariable
    )
    {
        static::addReference(
            $name,
            new FileReference(
                $dbName,
                $filePath,
                $pdoVariable
            )
        );
    }

    /**
     * Tries to get the PDOWrapper from the $LIBRARY with the given name.
     * An error is thrown and the script dies if the given Reference was not
     * found.
     *
     * @param string $name
     * @return PDOWrapper
     */
    public static function getWrapper(string $name)
    {
        // generate initial references, if any exist
        static::addInitialReferences();

        if (isset(static::$LIBRARY[$name])) {
            /** @var Reference $reference */
            $reference = static::$LIBRARY[$name];
            return $reference->getWrapper();
        } else {
            error_log("No reference to [$name] found in the library! Exiting...");
            die();
        }
    }

    /**
     * Checks to see if the input array has the given key.
     * If it does, that value is returned.
     * If not, an exception is thrown.
     *
     * @param int $index
     * @param array $array
     * @param string $key
     * @return mixed
     * @throws \Exception
     */
    private static function checkKey(int $index, array $array, string $key)
    {
        if (!isset($array[$key])) {
            throw new \Exception("Array at index [$index] does not have key [$key]");
        }
        return $array[$key];
    }

    private static function addInitialReferences()
    {
        if (!static::$INITIAL_REFERENCES_INITIALIZED) {
            static::$INITIAL_REFERENCES_INITIALIZED = true;


            try {
                foreach (static::getInitialReferences() as $index => $referenceData) {

                    /**
                     * Calls Library::checkKey for the current index/reference element
                     * and the input key.
                     *
                     * @see Library::checkKey()
                     *
                     * @param string $key
                     * @return mixed
                     */
                    $checkKey = function (string $key) use ($index, $referenceData) {
                        return static::checkKey($index, $referenceData, $key);
                    };

                    $type = $checkKey("type");
                    $name = $checkKey("name");
                    $dbName = $checkKey("dbName");

                    switch ($type) {
                        case "string":
                            $dsn = $checkKey("dsn");

                            // The following aren't required, so we don't use $checkKey
                            $username = $referenceData["username"] ?? '';
                            $password = $referenceData["password"] ?? '';
                            $options = $referenceData["options"] ?? [];

                            static::addStringReference(
                                $name,
                                $dbName,
                                $dsn,
                                $username,
                                $password,
                                $options
                            );
                            break;
                        case "file":
                            $filePath = $checkKey("filePath");
                            $pdoVariable = $checkKey("pdoVariable");

                            static::addFileReference(
                                $name,
                                $dbName,
                                $filePath,
                                $pdoVariable
                            );
                            break;
                        case "pdo":
                            $db = $checkKey("db");

                            static::addPDOReference(
                                $name,
                                $dbName,
                                $db
                            );
                            break;
                        default:
                            // Invalid type
                            throw new \Exception("Cannot create reference with type [$type].");
                    }
                }
            } catch (\Exception $e) {
                error_log("Error adding initial references to the library.");
                error_log($e->getMessage());
                error_log($e->getTraceAsString());
                die();
            }
        }
    }
}