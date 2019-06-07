<?php declare(strict_types=1);

namespace GridTable\Drivers;


/**
 * Class ApiDataSource
 *
 * @author  geniv
 * @package GridTable\Drivers
 */
class ApiDataSource extends ArrayDataSource
{

    public function __construct(callable $function)
    {

        dump($function);

        parent::__construct([]);
    }
}

