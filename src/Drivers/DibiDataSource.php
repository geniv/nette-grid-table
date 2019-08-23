<?php declare(strict_types=1);

namespace GridTable\Drivers;

use Traversable;


/**
 * Class DibiDataSource
 *
 * @author  geniv
 * @package GridTable\Drivers
 */
class DibiDataSource implements IDataSource
{
    private $data;


    /**
     * DibiDataSource constructor.
     *
     * @param $data
     */
    public function __construct($data)
    {
        $this->data = $data;
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
        return $this->data;
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
        return count($this->data);
    }


    /**
     * __toString.
     *
     * @return string
     */
    public function __toString()
    {
        // for support (string)$this->source in getCacheId() method
        return serialize($this->data);
    }
}

