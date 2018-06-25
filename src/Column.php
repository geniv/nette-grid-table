<?php declare(strict_types=1);

namespace GridTable;

use Nette\SmartObject;


/**
 * Class Column
 *
 * @author  geniv
 * @package GridTable
 */
class Column
{
    use SmartObject;

    const
        NAME = 'name',
        HEADER = 'header',
        ORDERING = 'ordering',
        ORDERING_STATE = 'state',
        ORDERING_NEXT_DIRECTION = 'next_direction',
        ORDERING_CURRENT_DIRECTION = 'current_direction',
        CALLBACK = 'callback';

    /** @var array */
    private $configure = [];


    /**
     * Column constructor.
     *
     * @param string      $name
     * @param string|null $header
     */
    public function __construct(string $name, string $header = null)
    {
        $this->configure[self::NAME] = $name;
        $this->configure[self::HEADER] = $header;
    }


    /*
    * LATTE
    */


    /**
     * Get caption.
     *
     * @return string
     */
    public function getCaption(): string
    {
        return ($this->configure[self::HEADER] ?: $this->configure[self::NAME]);
    }


    /**
     * Is ordering.
     *
     * @return bool
     */
    public function isOrdering(): bool
    {
        return $this->configure[self::ORDERING][self::ORDERING_STATE];
    }


    /**
     * Get order href.
     *
     * @return array
     */
    public function getOrderHref(): array
    {
        return [$this->configure[self::NAME], $this->configure[self::ORDERING][self::ORDERING_NEXT_DIRECTION]];
    }


    /**
     * Get current order.
     *
     * @return string
     */
    public function getCurrentOrder(): string
    {
        return $this->configure[self::ORDERING][self::ORDERING_CURRENT_DIRECTION] ?? '';
    }


    /**
     * Set order.
     *
     * @internal
     * @param string|null $direction
     */
    public function setOrder(string $direction = null)
    {
        $switchDirection = [
            null   => 'ASC',
            'ASC'  => 'DESC',
            'DESC' => null,
        ];

        $this->configure[self::ORDERING][self::ORDERING_CURRENT_DIRECTION] = $direction;
        $this->configure[self::ORDERING][self::ORDERING_NEXT_DIRECTION] = $switchDirection[$direction];
    }


    /**
     * Get value.
     *
     * @param $data
     * @return string
     */
    public function getValue($data): string
    {
        return (isset($this->configure[self::CALLBACK])) ? $this->configure[self::CALLBACK]($data) : $data[$this->configure[self::NAME]];
    }


    /*
    * PHP
    */


    /**
     * Set ordering.
     *
     * @param bool $ordering
     * @return Column
     */
    public function setOrdering(bool $ordering = true): self
    {
        $this->configure[self::ORDERING] = [
            self::ORDERING_STATE          => $ordering,
            self::ORDERING_NEXT_DIRECTION => 'ASC',
        ];
        return $this;
    }


    /**
     * Set callback.
     *
     * @param callable $callback
     * @return Column
     */
    public function setCallback(callable $callback): self
    {
        $this->configure[self::CALLBACK] = $callback;
        return $this;
    }


//    public function setSelect(bool $enable): self
//    {
//        //TODO selectovani radku podle hodnoty v selectu - filtrovani podle enum/select hodnot
//        return $this;
//    }
}
