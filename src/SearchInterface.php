<?php

namespace Bocharov\Search;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;

/**
 * Interface SearchInterface
 */
interface SearchInterface
{
    const DEFAULT_LIMIT = 10;

    /**
     * @param Criteria $criteria
     * @return ResultInterface
     */
    public function search(Criteria $criteria): ResultInterface;

    /**
     * @param Collection $documents
     * @return mixed
     */
    public function index(Collection $documents);
}
