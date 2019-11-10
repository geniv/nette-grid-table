<?php declare(strict_types=1);

namespace GridTable;

use Nette\SmartObject;


/**
 * Class Order
 *
 * @author  geniv
 * @package GridTable
 */
class OrderItem
{
    use SmartObject;

    /** @var bool */
    private $state = false;
    /** @var string */
    private $columnName = null, $name = null;
    /** @var string */
    private $currentDirection = null, $nextDirection = null;


    /**
     * OrderItem constructor.
     *
     * @param string $columnName
     */
    public function __construct(string $columnName)
    {
        $this->columnName = $columnName;
    }


    /**
     * Get state.
     *
     * @return bool
     */
    public function getState(): bool
    {
        return $this->state;
    }


    /**
     * Set state.
     *
     * @param bool $state
     * @return $this
     */
    public function setState(bool $state): self
    {
        $this->state = $state;
        return $this;
    }


    /**
     * Set name.
     *
     * @param string $name
     * @return $this
     */
    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }


    /**
     * Get current direction.
     *
     * @return string
     */
    public function getCurrentDirection(): string
    {
        return $this->currentDirection;
    }


    /**
     * Set current direction.
     *
     * @param string $direction
     * @return $this
     */
    public function setCurrentDirection(string $direction): self
    {
        $this->currentDirection = $direction;
        return $this;
    }


    /**
     * Set next direction.
     *
     * @param string $direction
     * @return $this
     */
    public function setNextDirection(string $direction): self
    {
        $this->nextDirection = $direction;
        return $this;
    }


    /**
     * Get href.
     *
     * @return array
     */
    public function getHref(): array
    {
        return [$this->columnName, $this->nextDirection];
    }
}
