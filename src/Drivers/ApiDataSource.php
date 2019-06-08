<?php declare(strict_types=1);

namespace GridTable\Drivers;

use ArrayObject;
use Dibi\IDataSource;
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
    private $count, $limit, $offset;
    /** @var array */
    private $order;


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
        $data = call_user_func_array($this->function, [$this->limit, $this->offset]);
        $iterator = new ArrayObject($data[$this->dataIndex]);

        // if order set
        if ($this->order) {
            $key = key($this->order);
            $direction = strtolower($this->order[$key]);
            // user order by value
            $iterator->uasort(function ($a, $b) use ($key, $direction) {
                if ($direction == 'asc') {
                    return $a[$key] > $b[$key];
                }
                if ($direction == 'desc') {
                    return $a[$key] < $b[$key];
                }
                return 0;
            });
        }
        return $iterator;
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
        return __CLASS__ . serialize($this->order) . $this->count . $this->limit . $this->offset;
    }
}

