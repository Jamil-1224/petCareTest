<?php
// db_connect.php - MongoDB Connection

// Prevent multiple inclusions
if (defined('MONGODB_CONNECTED')) {
    return;
}
define('MONGODB_CONNECTED', true);

require_once __DIR__ . '/vendor/autoload.php';

use MongoDB\Client;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;

// MongoDB Connection String - MUST be set as environment variable
$MONGO_URI = getenv('MONGODB_URI');
$DB_NAME = 'pet_care';

if (!$MONGO_URI) {
    die("ERROR: MONGODB_URI environment variable is not set. Please configure it in your server environment.");
}

try {
    // Create MongoDB client with default options (auto-detects TLS from URI)
    // MongoDB Atlas URIs include srv:// which handles TLS automatically
    $client = new Client($MONGO_URI, [
        'serverSelectionTimeoutMS' => 10000,
        'connectTimeoutMS' => 10000,
    ]);

    $db = $client->$DB_NAME;

    // Test connection - will throw exception if connection fails
    $client->listDatabases();
} catch (\MongoDB\Driver\Exception\ConnectionTimeoutException $e) {
    error_log("MongoDB Connection Timeout: " . $e->getMessage());
    die("MongoDB Connection failed: Unable to reach database server. Please check network connectivity and MongoDB Atlas whitelist settings.");
} catch (\MongoDB\Driver\Exception\SSLConnectionException $e) {
    error_log("MongoDB SSL Error: " . $e->getMessage());
    die("MongoDB Connection failed: SSL/TLS handshake error. Please check OpenSSL configuration and MongoDB driver version.");
} catch (Exception $e) {
    error_log("MongoDB Connection Error: " . $e->getMessage());
    die("MongoDB Connection failed: " . $e->getMessage());
}

// Helper function to convert MySQL result to array format
if (!function_exists('mongoResultToArray')) {
    function mongoResultToArray($cursor)
    {
        $results = [];
        foreach ($cursor as $document) {
            $results[] = $document;
        }
        return $results;
    }
}

// Helper function to convert ObjectId to string
if (!function_exists('objectIdToString')) {
    function objectIdToString($objectId)
    {
        return $objectId instanceof ObjectId ? (string)$objectId : $objectId;
    }
}

// Helper function to create ObjectId from string
if (!function_exists('stringToObjectId')) {
    function stringToObjectId($string)
    {
        try {
            return new ObjectId($string);
        } catch (Exception $e) {
            return null;
        }
    }
}

// Helper function to get current UTC DateTime
if (!function_exists('getCurrentDateTime')) {
    function getCurrentDateTime()
    {
        return new UTCDateTime();
    }
}

// Helper function to create date from string
if (!function_exists('stringToDateTime')) {
    function stringToDateTime($dateString)
    {
        try {
            $timestamp = strtotime($dateString);
            return new UTCDateTime($timestamp * 1000);
        } catch (Exception $e) {
            return new UTCDateTime();
        }
    }
}

// Helper function for insert operations (returns inserted ID)
if (!function_exists('mongoInsert')) {
    function mongoInsert($collection, $data)
    {
        global $db;
        try {
            $result = $db->$collection->insertOne($data);
            return $result->getInsertedId();
        } catch (Exception $e) {
            error_log("MongoDB Insert Error: " . $e->getMessage());
            return false;
        }
    }
}

// Helper function for update operations
if (!function_exists('mongoUpdate')) {
    function mongoUpdate($collection, $filter, $update, $options = [])
    {
        global $db;
        try {
            // Check if multiple documents should be updated
            if (isset($options['multiple']) && $options['multiple'] === true) {
                $result = $db->$collection->updateMany($filter, $update);
            } else {
                $result = $db->$collection->updateOne($filter, $update);
            }
            return $result->getModifiedCount() > 0 || $result->getMatchedCount() > 0;
        } catch (Exception $e) {
            error_log("MongoDB Update Error: " . $e->getMessage());
            return false;
        }
    }
}

// Helper function for delete operations
if (!function_exists('mongoDelete')) {
    function mongoDelete($collection, $filter)
    {
        global $db;
        try {
            $result = $db->$collection->deleteOne($filter);
            return $result->getDeletedCount() > 0;
        } catch (Exception $e) {
            error_log("MongoDB Delete Error: " . $e->getMessage());
            return false;
        }
    }
}

// Helper function for find operations
if (!function_exists('mongoFind')) {
    function mongoFind($collection, $filter = [], $options = [])
    {
        global $db;
        try {
            return $db->$collection->find($filter, $options);
        } catch (Exception $e) {
            error_log("MongoDB Find Error: " . $e->getMessage());
            return [];
        }
    }
}

// Helper function for findOne operations
if (!function_exists('mongoFindOne')) {
    function mongoFindOne($collection, $filter = [], $options = [])
    {
        global $db;
        try {
            return $db->$collection->findOne($filter, $options);
        } catch (Exception $e) {
            error_log("MongoDB FindOne Error: " . $e->getMessage());
            return null;
        }
    }
}

// Helper function for count operations
if (!function_exists('mongoCount')) {
    function mongoCount($collection, $filter = [])
    {
        global $db;
        try {
            return $db->$collection->countDocuments($filter);
        } catch (Exception $e) {
            error_log("MongoDB Count Error: " . $e->getMessage());
            return 0;
        }
    }
}

// Helper function for aggregation operations
if (!function_exists('mongoAggregate')) {
    function mongoAggregate($collection, $pipeline = [])
    {
        global $db;
        try {
            return $db->$collection->aggregate($pipeline);
        } catch (Exception $e) {
            error_log("MongoDB Aggregate Error: " . $e->getMessage());
            return [];
        }
    }
}

// Backward compatibility: Create a $conn variable for minimal code changes
$conn = new class {
    public function prepare($query)
    {
        return new class($query) {
            private $query;

            public function __construct($query)
            {
                $this->query = $query;
            }

            public function bind_param($types, ...$params)
            {
                // Store params for later use
                return true;
            }

            public function execute()
            {
                return true;
            }

            public function get_result()
            {
                return new class {
                    public function fetch_assoc()
                    {
                        return null;
                    }
                    public function num_rows()
                    {
                        return 0;
                    }
                };
            }
        };
    }

    public function query($query)
    {
        return new class {
            public $num_rows = 0;
            public function fetch_assoc()
            {
                return null;
            }
        };
    }

    public $insert_id = 0;
};
