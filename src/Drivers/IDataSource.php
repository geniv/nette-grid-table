<?php declare(strict_types=1);

namespace GridTable\Drivers;

use Countable;
use IteratorAggregate;


/**
 * Interface IDataSource
 * Provides an interface between a dataset and data-aware components.
 * Copy of `Dibi` Interface
 *
 * @author  geniv
 * @package GridTable\Drivers
 */
interface IDataSource extends Countable, IteratorAggregate
{
    //function \IteratorAggregate::getIterator();
    //function \Countable::count();
}
