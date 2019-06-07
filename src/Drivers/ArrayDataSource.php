<?php declare(strict_types=1);

namespace GridTable\Drivers;

use ArrayObject;
use Dibi\IDataSource;
use Traversable;


/**
 * Class ArrayDataSource
 *
 * @author  geniv
 * @package GridTable\Drivers
 */
class ArrayDataSource implements IDataSource
{
    /** @var ArrayObject */
    private $iterator;


    /**
     * ArrayDataSource constructor.
     *
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->iterator = new ArrayObject($data);
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
        return $this->iterator->getIterator();
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
        return $this->iterator->count();
    }


    /**
     * Order by.
     *
     * @param array $order
     */
    public function orderBy(array $order)
    {
        $key = key($order);
        $direction = strtolower($order[$key]);
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


    /**
     * Limit.
     *
     * @param int $limit
     * @return ArrayDataSource
     */
    public function limit(int $limit): self
    {
        return $this;
    }


    /**
     * Offset.
     *
     * @param int $offset
     * @return ArrayDataSource
     */
    public function offset(int $offset): self
    {
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
        return $this->iterator->serialize();
    }
}

