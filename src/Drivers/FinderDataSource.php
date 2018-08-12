<?php declare(strict_types=1);

namespace GridTable\Drivers;

use Nette\Utils\Finder;


/**
 * Class FinderDataSource
 *
 * @author  geniv
 * @package GridTable\Drivers
 */
class FinderDataSource extends ArrayDataSource
{

    /**
     * FinderDataSource constructor.
     *
     * @param Finder $finder
     */
    public function __construct(Finder $finder)
    {
        $result = [];
        foreach ($finder as $item) {
            $realPath = $item->getRealPath();
            $result[$realPath] = [
                'realPath' => $realPath,
                'mTime'    => $item->getMTime(),
                'size'     => $item->getSize(),
                'basename' => $item->getBasename(),
                'path'     => $item->getPath(),
            ];
        }
        parent::__construct($result);
    }
}

