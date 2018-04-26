<?php

namespace Symbiote\Cloudflare;

class PurgeAllTask extends \SilverStripe\Dev\BuildTask
{
    use PurgeTask;

    protected $title = 'Cloudflare Purge: Everything';

    protected $description = 'Purges everything from the cache. WARNING: You need to be *really* sure you want this.';

    public function callPurgeFunction(Cloudflare $client)
    {
        return $client->purgeAll();
    }
}
