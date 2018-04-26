<?php

namespace Symbiote\Cloudflare;

class PurgeImagesTask extends \SilverStripe\Dev\BuildTask
{
    use PurgeTask;

    protected $title = 'Cloudflare Purge: Images';

    protected $description = 'Purges all image files.';

    public function callPurgeFunction(Cloudflare $client)
    {
        return $client->purgeImages();
    }
}
