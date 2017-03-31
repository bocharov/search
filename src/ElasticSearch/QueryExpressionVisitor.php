<?php

namespace Bocharov\Search\ElasticSearch;

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Doctrine\Common\Collections\Expr\ExpressionVisitor;
use Doctrine\Common\Collections\Expr\Value;

/**
 * Class QueryExpressionVisitor
 */
class QueryExpressionVisitor extends ExpressionVisitor
{
    const OPERATORS_MAPPING = [
        Comparison::GT  => 'gt',
        Comparison::GTE => 'gte',
        Comparison::LT  => 'lt',
        Comparison::LTE => 'lte',
    ];

    private $query = [];

    private $filter = [];

    private $rangeFieldsOffsets = [];

    /**
     * Converts a comparison expression into the target query language output.
     *
     * @param Comparison $comparison
     *
     * @return mixed
     */
    public function walkComparison(Comparison $comparison)
    {
        $field = $comparison->getField();
        $value = $this->dispatch($comparison->getValue());
        switch ($comparison->getOperator()) {
            case Comparison::IN:
                //feed-like search - match terms from another index
                if (count($value) === 1 && strpos(current($value), '.') !== false) {
                    list($index, $type, $joinField) = explode(".", current($value));
                    $this->filter[] = [
                        'terms' => [
                            $field => [
                                'index' => $index,
                                'type'  => $type,
                                'path'  => $joinField,
                                'id'    => key($value),
                            ],
                        ],
                    ];
                } else {
                    $this->filter[] = [
                        'terms' => [
                            $field => $value,
                        ],
                    ];
                }
                break;

            case Comparison::EQ:
            case Comparison::IS:
                $this->filter[] = [
                    'term' => [
                        $field => $value,
                    ],
                ];
                break;

            case Comparison::GT:
            case Comparison::GTE:
            case Comparison::LT:
            case Comparison::LTE:
                $operator = self::OPERATORS_MAPPING[$comparison->getOperator()];
                if (isset($this->rangeFieldsOffsets[$field])) {
                    $this->filter[$this->rangeFieldsOffsets[$field]]['range'][$field][$operator] = $value;
                } else {
                    $this->filter[] = [
                        'range' => [
                            $field => [
                                $operator => $value,
                            ],
                        ],
                    ];
                    $this->rangeFieldsOffsets[$field] = count($this->filter) - 1;
                }

                break;

            case Comparison::NIN:
                //TODO: add support later
                break;

            case Comparison::NEQ:
                //TODO: add support later
                break;

            case Comparison::CONTAINS:
                $this->query[] = [
                    'match_phrase_prefix' => [
                        $comparison->getField() => $value,
                    ],
                ];
                break;

            default:
                throw new \InvalidArgumentException("Unknown comparison operator: ".$comparison->getOperator());
        }

        return;
    }

    /**
     * Converts a value expression into the target query language part.
     *
     * @param Value $value
     *
     * @return mixed
     */
    public function walkValue(Value $value)
    {
        return $value->getValue();
    }

    /**
     * Converts a composite expression into the target query language output.
     *
     * @param CompositeExpression $expr
     *
     * @return mixed
     */
    public function walkCompositeExpression(CompositeExpression $expr)
    {
        if ($expr->getType() !== CompositeExpression::TYPE_AND) {
            throw new \InvalidArgumentException("Only AND composite expressions are supported.");
        }

        foreach ($expr->getExpressionList() as $child) {
            $this->dispatch($child);
        }

        return;
    }

    /**
     * @return array
     */
    public function getQueryParams()
    {
        $queryParams = ['bool' => []];

        if (!empty($this->query)) {
            if (count($this->query) === 1) {
                $queryParams['bool']['must'] = $this->query;
            } else {
                $queryParams['bool']['should'] = $this->query;
            }
        }

        if (!empty($this->filter)) {
            $queryParams['bool']['filter'] = $this->filter;
        }

        if (empty($queryParams['bool'])) {
            throw new \InvalidArgumentException("Query params cannot be empty!");
        }

        return $queryParams;
    }
}
