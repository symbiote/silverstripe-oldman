<?php

namespace Symbiote\Cloudflare;

class PurgeCSSAndJavascriptTask extends PurgeTask
{
    protected $title = 'Cloudflare Purge: CSS and JavaScript';

    protected $description = 'Purges all CSS and JavaScript files.';

    public function callPurgeFunction(Cloudflare $client)
    {
        return $client->purgeCSSAndJavascript();
    }
}
