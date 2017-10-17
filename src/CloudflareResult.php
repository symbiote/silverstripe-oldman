<?php

namespace Symbiote\Cloudflare;

class CloudflareResult
{
    /**
     * @var array
     */
    protected $successes = array();

    /**
     * @var array
     */
    protected $errors = array();

    public function __construct(array $files, array $errorRecords)
    {
        // Determine what purged files were un-successful.
        $purgedFiles = $files;
        foreach ($errorRecords as $errorRecord) {
            foreach ($purgedFiles as $key => $url) {
                if (strpos($errorRecord->message, $url) !== false) {
                    unset($purgedFiles[$key]);
                }
            }
        }

        // Apply to this object
        $this->successes = $purgedFiles;
        if ($errorRecords) {
            $this->errors = array();
            foreach ($errorRecords as $errorRecord) {
                $this->errors[] = $errorRecord->message;
            }
        }
    }

    /**
     * @var array
     */
    public function getSuccesses()
    {
        return $this->successes;
    }

    /**
     * @var array
     */
    public function getErrors()
    {
        return $this->errors;
    }
}
