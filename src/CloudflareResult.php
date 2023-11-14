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
        // Apply to this object
        $this->errors = $errorRecords;
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
