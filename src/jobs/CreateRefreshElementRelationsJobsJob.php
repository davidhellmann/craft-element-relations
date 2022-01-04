<?php

namespace internetztube\elementRelations\jobs;

use Craft;
use craft\queue\BaseJob;
use internetztube\elementRelations\services\CacheService;
use internetztube\elementRelations\services\ElementRelationsService;
use Tightenco\Collect\Support\Collection;

/**
 * Since the refresh jobs are very time and computation intensive, the jobs are separated into 1000 items chunks.
 * Class CreateRefreshElementRelationsJobsJob
 * @package internetztube\elementRelations\jobs
 */
class CreateRefreshElementRelationsJobsJob extends BaseJob
{
    public $description = 'Create Refresh Element Relations Cache Jobs';

    /** @var bool */
    public $force = false;

    public function execute($queue)
    {
        if (!CacheService::useCache()) {
            return;
        }
        $rows = ElementRelationsService::getElementsWithElementRelationsField();
        $queue = Craft::$app->getQueue();

        $jobSize = 100;
        $chunks = collect($rows)->chunk($jobSize);
        $count = $chunks->count();

        $chunks->each(function (Collection $chunk, $index) use ($queue, $count) {
            $job = new RefreshElementRelationsJob([
                'description' => sprintf('Refresh Element Relations Cache %d/%d', $index + 1, $count),
                'force' => $this->force,
                'elements' => $chunk->values()->toArray()
            ]);
            $queue->push($job);
            $queue->setProgress(($index + 1) * 100 / $count);
        });
    }
}