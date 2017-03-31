<?php

namespace Bocharov\Search;

/**
 * Interface ResultInterface
 */
interface ResultInterface
{
    /**
     * @return mixed
     */
    public function getErrors();

    /**
     * @return int
     */
    public function getFound(): int;

    /**
     * @return int
     */
    public function getTime(): int;

    /**
     * @return array
     */
    public function toArray(): array;
}
