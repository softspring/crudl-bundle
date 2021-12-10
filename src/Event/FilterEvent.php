<?php

namespace Softspring\CrudlBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

class FilterEvent extends Event
{
    /**
     * @var array
     */
    protected $filters;

    /**
     * @var array
     */
    protected $orderSort;

    /**
     * @var int
     */
    protected $page;

    /**
     * @var int|null
     */
    protected $rpp;

    /**
     * FilterEvent constructor.
     *
     * @param array    $filters
     * @param array    $orderSort
     * @param int      $page
     * @param int|null $rpp
     */
    public function __construct(array $filters, array $orderSort, int $page, ?int $rpp)
    {
        $this->filters = $filters;
        $this->orderSort = $orderSort;
        $this->page = $page;
        $this->rpp = $rpp;
    }

    /**
     * @return array
     */
    public function getFilters(): array
    {
        return $this->filters;
    }

    /**
     * @param array $filters
     */
    public function setFilters(array $filters): void
    {
        $this->filters = $filters;
    }

    /**
     * @return array
     */
    public function getOrderSort(): array
    {
        return $this->orderSort;
    }

    /**
     * @param array $orderSort
     */
    public function setOrderSort(array $orderSort): void
    {
        $this->orderSort = $orderSort;
    }

    /**
     * @return int
     */
    public function getPage(): int
    {
        return $this->page;
    }

    /**
     * @param int $page
     */
    public function setPage(int $page): void
    {
        $this->page = $page;
    }

    /**
     * @return int|null
     */
    public function getRpp(): ?int
    {
        return $this->rpp;
    }

    /**
     * @param int|null $rpp
     */
    public function setRpp(?int $rpp): void
    {
        $this->rpp = $rpp;
    }
}