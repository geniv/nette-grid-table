<?php declare(strict_types=1);

namespace GridTable\Drivers;

use ArrayObject;
use Traversable;


/**
 * Class ApiDataSource
 *
 * @author  geniv
 * @package GridTable\Drivers
 */
class ApiDataSource implements IDataSource
{
    /** @var callable */
    private $function;
    /** @var string */
    private $dataIndex;
    /** @var int */
    private $count, $limit = 0, $offset = 0;
    /** @var array */
    private $where = [], $order = [];
    /** @var ArrayObject */
    private $iterator;


    /**
     * ApiDataSource constructor.
     *
     * @param callable $function
     * @param string   $countIndex
     * @param string   $dataIndex
     */
    public function __construct(callable $function, string $countIndex, string $dataIndex)
    {
        $this->function = $function;
        $this->dataIndex = $dataIndex;

        // first call api callback for count
        $data = call_user_func_array($this->function, [0, 0]);
        $this->count = $data[$countIndex];

        $this->iterator = new ArrayObject($data[$this->dataIndex]);
    }


    /**
     * Load data.
     */
    private function loadData()
    {
        if ($this->iterator->count() != $this->count) {
            $data = call_user_func_array($this->function, [$this->limit, $this->offset]);
            $this->iterator->exchangeArray($data[$this->dataIndex]);
        }
    }


    /**
     * Retrieve an external iterator
     *
     * @link http://php.net/manual/en/iteratoraggregate.getiterator.php
     * @return Traversable An instance of an object implementing <b>Iterator</b> or
     * <b>Traversable</b>
     * @since 5.0.0
     */
    public function getIterator(): Traversable
    {
        if ($this->where) {
            $this->limit = 999;
        }

        $this->loadData();

        // if set order
        if ($this->order) {
            $key = key($this->order);
            $direction = strtolower($this->order[$key]);
            // user order by value
            $this->iterator->uasort(function ($a, $b) use ($key, $direction) {
                if ($direction == 'asc') {
                    return $a[$key] > $b[$key];
                }
                if ($direction == 'desc') {
                    return $a[$key] < $b[$key];
                }
                return 0;
            });
        }

        // if set where
        if ($this->where) {
//            bdump($this->where);
            // transfer to array and filter correct value
            $filter = array_filter((array) $this->iterator, function ($item) {
                $state = false;
                foreach ($this->where as $key => $where) {
                    if (is_array($where)) {
                        // compare for array
                        if (in_array($item[$key], $where)) {
                            $state = true;
                        }
                    }

                    // compare for single number
                    if (is_numeric($where)) {
                        if ($item[$key] == $where) {
                            $state = true;
                        }
                    }
                }
                return $state;
            });
            // transfer to back ArrayObject
            $this->iterator = new ArrayObject($filter);
            $this->count = $this->iterator->count();
        }
        return $this->iterator;
    }


    /**
     * Count elements of an object
     *
     * @link http://php.net/manual/en/countable.count.php
     * @return int The custom count as an integer.
     * </p>
     * <p>
     * The return value is cast to an integer.
     * @since 5.1.0
     */
    public function count()
    {
        return $this->count;
    }


    /**
     * Order by.
     *
     * @param array $order
     * @return ApiDataSource
     */
    public function orderBy(array $order): self
    {
        $this->order = $order;
        return $this;
    }


    /**
     * Limit.
     *
     * @param int $limit
     * @return ApiDataSource
     */
    public function limit(int $limit): self
    {
        $this->limit = $limit;
        $this->loadData();
        return $this;
    }


    /**
     * Offset.
     *
     * @param int $offset
     * @return ApiDataSource
     */
    public function offset(int $offset): self
    {
        $this->offset = $offset;
        $this->loadData();
        return $this;
    }


    /**
     * Where.
     * Special version for API driver - always use OR!!!
     *
     * @param array $condition
     * @return ApiDataSource
     */
    public function where(array $condition): self
    {
        $this->where = array_merge_recursive($this->where, $condition);
        return $this;
    }


    /**
     * __toString.
     *
     * @return string
     */
    public function __toString()
    {
        // for support (string)$this->source in getCacheId() method
        return $this->limit . $this->offset . $this->count . $this->iterator->serialize();
    }
}

