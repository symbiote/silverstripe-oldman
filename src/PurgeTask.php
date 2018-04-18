<?php

namespace Symbiote\Cloudflare;

use BuildTask;
use Injector;
use Director;
use SS_Log;

abstract class PurgeTask extends BuildTask
{
    abstract protected function callPurgeFunction(Cloudflare $client);

    public function run($request = null)
    {
        $client = Injector::inst()->get(Cloudflare::class);
        if (!$client->config()->enabled) {
            $this->log('Cloudflare is not currently enabled in YML.');
            return;
        }

        // If accessing via web-interface, add an "are you sure" message.
        if (!\Director::is_cli()) {
            if ($request->getVar('purge') != true) {
                $this->log('Append "?purge=true" to the URL to confirm execution.');
                return;
            }
        }

        $errorMessage = '';
        $success = false;

        // Process
        $startTime = microtime(true);
        $result = $this->callPurgeFunction($client);
        $timeTakenInSeconds = number_format(microtime(true) - $startTime, 2, '.', '');

        // Show output
        $status = 'PURGE SUCCESS';

        $successes = $result->getSuccesses();
        if ($successes) {
            $this->log('Successes:', E_USER_WARNING);
            foreach ($successes as $success) {
                $this->log($success, E_USER_WARNING);
            }
        }

        $errors = $result->getErrors();

        if ($errors) {
            $status = 'PURGE ERRORS';
            if ($errors) {
                echo Director::is_cli() ? "\n" : '<br/>';
                $this->log('Error(s):', E_USER_WARNING);
                foreach ($errors as $error) {
                    $this->log($error, E_USER_WARNING);
                }
            }
        }

        // If no successes or errors, assume success.
        // ie. this is for purge everything.
        echo Director::is_cli() ? "\n" : '<br/>';
        if (!$successes && !$errors) {
            $this->log($status.'.', E_USER_WARNING);
        } else {
            $this->log($status.'. ('.count($successes).' successes, '.count($errors).' failed)', E_USER_WARNING);
        }
        $this->log('Time taken: '.$timeTakenInSeconds.' seconds.');
    }

    protected function log($message, $errorType = null)
    {
        $newline = Director::is_cli() ? "\n" : "<br/>";
        echo $message.$newline;
        if ($errorType) {
            SS_Log::log($message, $errorType);
        }
    }
}
