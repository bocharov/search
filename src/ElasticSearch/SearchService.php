<?php

namespace Bocharov\Search\ElasticSearch;

use Bocharov\Search\ResultInterface;
use Bocharov\Search\SearchInterface;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Elasticsearch\ClientBuilder;

/**
 * Class SearchService
 */
class SearchService implements SearchInterface
{
    /**
     * @var \Elasticsearch\Client
     */
    protected $client;

    /**
     * @var string
     */
    protected $index;

    /**
     * @var string
     */
    protected $type;

    /**
     * SearchService constructor.
     * @param array  $config
     * @param string $index
     * @param string $type
     */
    public function __construct(array $config, string $index, string $type)
    {
        $this->client = ClientBuilder::fromConfig($config);
        $this->index = $index;
        $this->type = $type;
    }

    /**
     * {@inheritdoc}
     */
    public function search(Criteria $criteria): ResultInterface
    {
        $expr = $criteria->getWhereExpression();
        $visitor = new QueryExpressionVisitor();
        $visitor->dispatch($expr);
        $queryParams = $visitor->getQueryParams();

        $params = [
            'index' => $this->index,
            'type'  => $this->type,
            'body'  => [
                'query' => $queryParams,
            ],
            'from'  => $criteria->getFirstResult() ?? 0,
            'size'  => $criteria->getMaxResults() ?? self::DEFAULT_LIMIT,
        ];

        $orderings = $criteria->getOrderings();
        if (!empty($orderings)) {
            $sorts = [];
            foreach ($orderings as $field => $order) {
                if ($field === 'score') {
                    $sorts[] = '_score';
                } else {
                    $sorts[] = [
                        $field => strtolower($order),
                    ];
                }
            }
            $params['body']['sort'] = $sorts;
        }

        $response = $this->client->search($params);

        return new Result($response);
    }

    /**
     * {@inheritdoc}
     */
    public function index(Collection $documents)
    {
        if ($documents->isEmpty()) {
            return;
        }

        $params = [
            'index' => $this->index,
            'type'  => $this->type,
            'body'  => [],
        ];
        foreach ($documents as $document) {
            $data = $document->getIndexData();
            $params['body'][] = [
                'index' => [
                    '_id' => $data['id'],
                ],
            ];
            unset($data['id']);
            $params['body'][] = $data;
        }

        $this->client->bulk($params);
    }
}
