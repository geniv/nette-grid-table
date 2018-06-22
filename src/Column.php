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
        ORDERING_DIRECTION = 'direction',
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
     * Get href.
     *
     * @return array
     */
    public function getOrdering(): array
    {
        return [$this->configure[self::NAME], $this->configure[self::ORDERING][self::ORDERING_DIRECTION]];
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
            self::ORDERING_STATE     => $ordering,
            self::ORDERING_DIRECTION => 'ASC',
        ];
        return $this;
    }


    /**
     * Set order direction
     *
     * @param string|null $direction
     */
    public function setOrderDirection(string $direction = null)
    {
        $this->configure[self::ORDERING][self::ORDERING_DIRECTION] = $direction;
    }


    /**
     * Get order direction.
     *
     * @return string
     */
    public function getOrderDirection(): string
    {
        return $this->configure[self::ORDERING][self::ORDERING_DIRECTION] ?? '';
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
}
