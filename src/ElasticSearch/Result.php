<?php

namespace Bocharov\Search\ElasticSearch;

use Bocharov\Search\ResultInterface;

/**
 * Class Result
 */
class Result implements ResultInterface
{
    /**
     * @var array
     */
    private $response;

    /**
     * Result constructor.
     * @param array $response
     */
    public function __construct(array $response)
    {
        $this->response = $response;
    }

    /**
     * {@inheritdoc}
     */
    public function getErrors()
    {
        return $this->response['errors'] ?? [];
    }

    /**
     * {@inheritdoc}
     */
    public function getFound(): int
    {
        return $this->response['hits']['total'];
    }

    /**
     * {@inheritdoc}
     */
    public function getTime(): int
    {
        return $this->response['took'];
    }

    /**
     * {@inheritdoc}
     */
    public function toArray(): array
    {
        return array_column($this->response['hits']['hits'], '_id');
    }
}
