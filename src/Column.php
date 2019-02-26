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
        DATA = 'data',
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
        return $this->configure[self::ORDERING][self::ORDERING_STATE] ?? false;
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
     * Get data.
     *
     * @param string|null $index
     * @return mixed|null
     */
    public function getData(string $index = null)
    {
        $data = $this->configure[self::DATA] ?? null;
        return ($index ? ($data[$index] ?? null) : $data);
    }


    /**
     * Get value.
     *
     * @param $data
     * @return string
     */
    public function getValue($data): string
    {
        $value = (isset($this->configure[self::CALLBACK]) ? $this->configure[self::CALLBACK]($data, $this) : $data[$this->configure[self::NAME]]);
        if (isset($this->configure[self::TEMPLATE])) {
            $template = $this->gridTable->getTemplate();
            $template->column = $this;
            $template->value = $value;
            foreach ($this->getData() ?? [] as $key => $val) {
                $template->$key = $val;
            }
            $template->setFile($this->configure[self::TEMPLATE]);
            return (string) $template;
        }
        return (string) $value;
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
     * Set data.
     *
     * @param array $data
     * @return Column
     */
    public function setData(array $data): self
    {
        $this->configure[self::DATA] = $data;
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
     * @param array  $data
     * @return Column
     */
    public function setTemplatePath(string $path, array $data = []): self
    {
        $this->configure[self::TEMPLATE] = $path;
        if ($data) {
            $this->setData(array_merge($this->configure[self::DATA] ?? [], $data));
        }
        return $this;
    }


//    public function setSelect(bool $enable): self
//    {
//        //TODO selectovani radku podle hodnoty v selectu - filtrovani podle enum/select hodnot
//        return $this;
//    }
}
