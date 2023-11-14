<?php

namespace Symbiote\Cloudflare;

use SilverStripe\Control\Director;
use SilverStripe\Core\Injector\Injector;

//
// NOTE(Jake): 2018-04-26
//
// We changed this from a class extending BuildTask to a trait as
// any classes that extended this abstract class wouldn't appear in
// the dev/tasks list.
//
trait PurgeTask
{
    abstract protected function callPurgeFunction(Cloudflare $client);

    public function endRun($request)
    {
        $client = Injector::inst()->get(Cloudflare::CLOUDFLARE_CLASS);
        if (!$client->config()->enabled) {
            $this->log('Cloudflare is not currently enabled in YML.');
            return;
        }

        // If accessing via web-interface, add an "are you sure" message.
        if (!Director::is_cli()) {
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

        $errors = $result->getErrors();

        if ($errors) {
            $status = 'PURGE ERRORS';
            if ($errors) {
                echo Director::is_cli() ? "\n" : '<br/>';
                $this->log('Error(s):');
                foreach ($errors as $error) {
                    $this->log($error);
                }
            }
        }

        // If no successes or errors, assume success.
        // ie. this is for purge everything.
        echo Director::is_cli() ? "\n" : '<br/>';
        if (!$errors) {
            $this->log($status.'.');
        } else {
            $this->log($status.'. ('.count($errors).' failed)');
        }
        $this->log('Time taken: '.$timeTakenInSeconds.' seconds.');
    }

    public function run($request)
    {
        $this->endRun($request);
    }

    protected function log($message)
    {
        $newline = Director::is_cli() ? "\n" : "<br/>";
        echo $message.$newline;
    }
}
