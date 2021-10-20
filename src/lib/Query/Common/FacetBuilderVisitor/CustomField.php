<?php

/**
 * NovaeZSolrSearchExtraBundle.
 *
 * @package   NovaeZSolrSearchExtraBundle
 *
 * @author    Novactive
 * @copyright 2020 Novactive
 * @license   https://github.com/Novactive/NovaeZSolrSearchExtraBundle/blob/master/LICENSE
 */

namespace Novactive\EzSolrSearchExtra\Query\Common\FacetBuilderVisitor;

use eZ\Publish\API\Repository\Values\Content\Query\FacetBuilder;
use EzSystems\EzPlatformSolrSearchEngine\Query\FacetBuilderVisitor;
use EzSystems\EzPlatformSolrSearchEngine\Query\FacetFieldVisitor;
use Novactive\EzSolrSearchExtra\Query\Common\FacetBuilder\CustomFieldFacetBuilder;
use Novactive\EzSolrSearchExtra\Values\Content\Search\Facet\CustomFieldFacet;

class CustomField extends FacetBuilderVisitor implements FacetFieldVisitor
{
    /**
     * {@inheritdoc}.
     */
    public function mapField($field, array $data, FacetBuilder $facetBuilder)
    {
        return new CustomFieldFacet(
            [
                'name'    => $facetBuilder->name,
                'entries' => $this->mapData($data),
                'field'   => $facetBuilder->field,
            ]
        );
    }

    /**
     * {@inheritdoc}.
     */
    protected function mapData(array $data)
    {
        $values = [];
        for ($i=0; $i < count($data); ++$i) {
            $key = $data[$i];
            if (null === $key) {
                $key = 'null';
            }
            $values[$key] = $data[$i + 1];
            ++$i;
        }

        return $values;
    }

    /**
     * {@inheritdoc}.
     */
    public function canVisit(FacetBuilder $facetBuilder)
    {
        return $facetBuilder instanceof CustomFieldFacetBuilder;
    }

    /**
     * @param CustomFieldFacetBuilder $facetBuilder
     * @param string                  $fieldId
     *
     * @return array|string[]
     */
    public function visitBuilder(FacetBuilder $facetBuilder, $fieldId)
    {
        $excludeTags = ['dt'];
        if ($facetBuilder->excludeTags) {
            array_push($excludeTags, ...$facetBuilder->excludeTags);
        }

        $excludeTags = implode(',', $excludeTags);

        $facetParams = [
            'facet.field'                             => "{!ex={$excludeTags} key=${fieldId}}$facetBuilder->field",
            "f.{$facetBuilder->field}.facet.limit"    => $facetBuilder->limit,
            "f.{$facetBuilder->field}.facet.mincount" => $facetBuilder->minCount,
            "f.{$facetBuilder->field}.facet.missing"  => $facetBuilder->missing ? 'true' : 'false',
        ];

        if(!empty($facetBuilder->excludeEntries)) {
            $facetParams["f.{$facetBuilder->field}.facet.excludeTerms"] = implode(',', $facetBuilder->excludeEntries);
        }

        return $facetParams;
    }
}
