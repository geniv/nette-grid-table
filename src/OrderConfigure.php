<?php declare(strict_types=1);

namespace GridTable;

use Nette\SmartObject;


/**
 * Class Order
 *
 * @author  geniv
 * @package GridTable
 */
class OrderConfigure
{
    use SmartObject;
    /** @var array */
    private $data = [];


    /**
     * Get column.
     *
     * @param string $name
     * @return OrderItem
     */
    public function getColumn(string $name): OrderItem
    {
        if (!isset($this->data[$name])) {
            $this->data[$name] = new OrderItem($name);
        }
        return $this->data[$name];
    }
}
