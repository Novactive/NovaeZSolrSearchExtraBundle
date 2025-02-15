<?php

namespace Novactive\EzSolrSearchExtra\ResultExtractor;

use Ibexa\Contracts\Core\Repository\Values\Content\Query\Aggregation;
use Ibexa\Contracts\Core\Repository\Values\Content\Search\AggregationResult;
use Ibexa\Contracts\Core\Repository\Values\Content\Search\AggregationResult\TermAggregationResult;
use Ibexa\Contracts\Solr\ResultExtractor\AggregationResultExtractor;
use Novactive\EzSolrSearchExtra\ResultExtractor\AggregationKeyMapper\AbstractRawTermAggregationKeyMapper;
use Novactive\EzSolrSearchExtra\ResultExtractor\AggregationKeyMapper\RawTermAggregationKeyMapper;
use Novactive\EzSolrSearchExtra\Search\AggregationResult\RawTermAggregationResultEntry;
use stdClass;

class RawTermAggregationResultExtractor implements AggregationResultExtractor
{
    /** @var \Novactive\EzSolrSearchExtra\ResultExtractor\AggregationKeyMapper\AbstractRawTermAggregationKeyMapper */
    private $keyMapper;

    /** @var string */
    private $aggregationClass;

    /** @var \Ibexa\Contracts\Solr\ResultExtractor\AggregationResultExtractor */
    protected $aggregationResultExtractor;

    public function __construct(
        string $aggregationClass,
        AggregationResultExtractor $aggregationResultExtractor,
        AbstractRawTermAggregationKeyMapper $keyMapper = null
    ) {
        if (null === $keyMapper) {
            $keyMapper = new RawTermAggregationKeyMapper();
        }

        $this->keyMapper = $keyMapper;
        $this->aggregationClass = $aggregationClass;
        $this->aggregationResultExtractor = $aggregationResultExtractor;
    }

    public function canVisit(Aggregation $aggregation, array $languageFilter): bool
    {
        return $aggregation instanceof $this->aggregationClass;
    }

    public function extract(Aggregation $aggregation, array $languageFilter, stdClass $data): AggregationResult
    {
        $entries = [];
        if (isset($data->buckets)) {
            $mappedKeys = $this->keyMapper->map(
                $aggregation,
                $languageFilter,
                $this->getKeys($data)
            );

            foreach ($data->buckets as $bucket) {
                $key = $bucket->val;
                if (isset($mappedKeys[$key])) {
                    $nestedAggregationtsResults = [];
                    if (!empty($aggregation->nestedAggregations)) {
                        foreach ($aggregation->nestedAggregations as $nestedAggregation) {
                            $name = $nestedAggregation->getName();
                            if (isset($bucket->{$name})) {
                                $nestedAggregationtsResults[$name] = $this->aggregationResultExtractor->extract(
                                    $nestedAggregation,
                                    $languageFilter,
                                    $bucket->{$name}
                                );
                            }
                        }
                    }

                    $entries[] = new RawTermAggregationResultEntry(
                        $key,
                        $bucket->count,
                        $mappedKeys[$key]['name'] ?? null,
                        $mappedKeys[$key]['identifier'] ?? null,
                        $nestedAggregationtsResults
                    );
                }
            }
        }

        return new TermAggregationResult($aggregation->getName(), $entries);
    }

    private function getKeys(stdClass $data): array
    {
        $keys = [];
        foreach ($data->buckets as $bucket) {
            $keys[] = $bucket->val;
        }

        return $keys;
    }
}
