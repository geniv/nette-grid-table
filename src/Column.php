<?php declare(strict_types=1);

namespace GridTable;

use GeneralForm\ITemplatePath;
use Nette\SmartObject;


/**
 * Class Column
 *
 * @author  geniv
 * @package GridTable
 */
class Column implements ITemplatePath
{
    use SmartObject;

    const
        NAME = 'name',
        HEADER = 'header',
        ORDERING = 'ordering',
        ORDERING_STATE = 'state',
        ORDERING_NEXT_DIRECTION = 'next_direction',
        ORDERING_CURRENT_DIRECTION = 'current_direction',
        CALLBACK = 'callback',
        TEMPLATE = 'template';

    /** @var array */
    private $configure = [];
    /** @var GridTable */
    private $gridTable;


    /**
     * Column constructor.
     *
     * @param GridTable   $gridTable
     * @param string      $name
     * @param string|null $header
     */
    public function __construct(GridTable $gridTable, string $name, string $header = null)
    {
        $this->gridTable = $gridTable;
        $this->configure[self::NAME] = $name;
        $this->configure[self::HEADER] = $header;
    }


    /*
    * LATTE
    */


    /**
     * Get name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->configure[self::NAME];
    }


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
        $value = (isset($this->configure[self::CALLBACK]) ? $this->configure[self::CALLBACK]($data) : $data[$this->configure[self::NAME]]);
        if (isset($this->configure[self::TEMPLATE])) {
            $template = $this->gridTable->getTemplate();
            $template->column = $this;
            $template->value = $value;
            $template->setFile($this->configure[self::TEMPLATE]);
            return (string) $template;
        }
        return $value;
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


    /**
     * Set template path.
     *
     * @param string $path
     */
    public function setTemplatePath(string $path)
    {
        $this->configure[self::TEMPLATE] = $path;
    }


//    public function setSelect(bool $enable): self
//    {
//        //TODO selectovani radku podle hodnoty v selectu - filtrovani podle enum/select hodnot
//        return $this;
//    }
}
