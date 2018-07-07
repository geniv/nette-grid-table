<?php declare(strict_types=1);

namespace GridTable\Drivers;

use ArrayObject;
use Dibi\IDataSource;


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
    public function getIterator()
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
        $direction = $order[$key];
        // user order by value
        $this->iterator->uasort(function ($a, $b) use ($key, $direction) {
            if ($direction == 'ASC') {
                return $a[$key] > $b[$key];
            }
            if ($direction == 'DESC') {
                return $a[$key] < $b[$key];
            }
            return 0;
        });
    }
}

