# Search

## About ##

The Search Service vendor provides abstract layer to perform search queries using Doctrine Criteria as input.

Currently, there is Elasticsearch implementation of this layer, but it's possible to add Solr or Sphinx or any other search engine support.

## Installation ##

Require the vendor and its dependencies with composer:

```bash
$ composer require bocharov/search
```

## Usage ##

1. Define Search Service instance like this

    ```yaml
        services:
            search.interests.service:
                class: Bocharov\Search\ElasticSearch\SearchService
                arguments:
                    - hosts: ["%elasticsearch.host%"]
                      logger: '@logger'
                    - interests
                    - interest
    ```

2. Create Doctrine Criteria with desired parameters and perform search

    ```php
    <?php
        $criteria = Criteria::create()
            ->where(Criteria::expr()->contains('name', $term))
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        if ($type) {
            $criteria->andWhere(Criteria::expr()->eq('type', $type));
        }

        if ($parentId) {
            $criteria->andWhere(Criteria::expr()->eq('parentId', $parentId));
        }

        $interestIds = $this->searchService->search($criteria)->toArray();
    ```

3. Add desired orderings if you need

    ```php
    <?php
        $criteria = Criteria::create()
            ->where(Criteria::expr()->contains('name', $term))
            ->andWhere(Criteria::expr()->contains('username', $term))
            ->orderBy([
                'score'             => Criteria::DESC,
                'followersNumber'   => Criteria::DESC,
            ])
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        $userIds = $this->searchService->search($criteria)->toArray();
    ```

4. Perform indexation of entities which implement IndexableInterface

    ```php
    <?php
        $this->searchService->index($searchUsers);
    ```

I suggest to use EventBusBundle in order to generate and process search indexation events asynchronously.
