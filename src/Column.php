<?php declare(strict_types=1);

namespace GridTable;

use DateInterval;
use GeneralForm\ITemplatePath;
use Nette\SmartObject;
use Nette\Utils\DateTime;
use Nette\Utils\Html;
use stdClass;


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
//        NAME = 'name',
//        HEADER = 'header',
//        FILTER = 'filter',
//        ORDERING_NAME = 'ordering-name',
//        ORDERING_STATE = 'ordering-state',
//        ORDERING_NEXT_DIRECTION = 'ordering-next_direction',
//        ORDERING_CURRENT_DIRECTION = 'ordering-current_direction',
        DATA = 'data',
        CALLBACK = 'callback',
        TEMPLATE = 'template';

    /** @var string */
    private $name, $header, $orderColumn, $orderCurrentDirection = '', $orderNextDirection = 'ASC';
    /** @var bool */
    private $orderState = false;

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
        $this->name = $name;
        $this->header = $header;
    }


    /**
     * Set order.
     *
     * @noinspection PhpUnused
     * @param string|null $direction
     * @internal
     */
    public function setOrder(string $direction = null)
    {
        $switchDirection = [
            null   => 'ASC',
            'ASC'  => 'DESC',
            'DESC' => null,
        ];

        $this->orderCurrentDirection = $direction;
        $this->orderNextDirection = $switchDirection[$direction];

//        $this->gridTable->orderConfigure->getColumn($this->name)
//            ->setCurrentDirection($direction)
//            ->setNextDirection($switchDirection[$direction]);
//        $columns = $this->globalConfigure->getConfigure(GridTable::GLOBAL_ORDER);
//        $columns[$this->name][self::ORDERING_CURRENT_DIRECTION] = $direction;
//        $columns[$this->name][self::ORDERING_NEXT_DIRECTION] = $switchDirection[$direction];
    }


    /**
     * Get order column.
     *
     * @return string
     * @internal
     */
    public function getOrderColumn(): string
    {
        return $this->orderColumn;
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
        return $this->name;
    }


    /**
     * Get caption.
     *
     * @noinspection PhpUnused
     * @return string
     */
    public function getCaption(): string
    {
        return ($this->header ?: $this->name);
    }


//    /**
//     * Is filter.
//     *
//     * @return bool
//     */
//    public function isFilter(): bool
//    {
//        return (bool) ($this->configure[self::FILTER] ?? false);
//    }


//    /**
//     * Get filter.
//     *
//     * @return array
//     */
//    public function getFilter(): array
//    {
//        if (is_bool($this->configure[self::FILTER])) {
//            return [];
//        }
//        return ($this->configure[self::FILTER] ?? []);
//    }


    /**
     * Is ordering.
     *
     * @return bool
     */
    public function isOrdering(): bool
    {
        return $this->orderState;
//        $columns = $this->globalConfigure->getConfigure(GridTable::GLOBAL_ORDER);
//        return ($columns[$this->name][self::ORDERING_STATE] ?? false);
    }


    /**
     * Get order href.
     *
     * @return array
     */
    public function getOrderHref(): array
    {
        return [$this->name, $this->orderNextDirection];
//        return $this->gridTable->orderConfigure->getColumn($this->name)->getHref();
//        return [$columns[$this->name][self::ORDERING_NAME], $columns[$this->name][self::ORDERING_NEXT_DIRECTION] ?? null];
    }


    /**
     * Get current order.
     *
     * @return string
     */
    public function getCurrentOrder(): string
    {
        return $this->orderCurrentDirection;
//        return $this->gridTable->orderConfigure->getColumn($this->name)->getCurrentDirection();
//        $columns = $this->globalConfigure->getConfigure(GridTable::GLOBAL_ORDER);
//        return $columns[$this->name][self::ORDERING_CURRENT_DIRECTION] ?? '';
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
        $value = (isset($this->configure[self::CALLBACK]) ? $this->configure[self::CALLBACK]($data, $this) : $data[$this->name]);
        if (isset($this->configure[self::TEMPLATE])) {
            $template = $this->gridTable->getTemplate();
            /** @var stdClass $template */
            $template->column = $this;
            $template->value = $value;
            $template->data = $data;
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
     * @param bool $state
     * @return Column
     */
    public function setOrdering(bool $state = true): self
    {
        $this->orderState = $state;
        $this->orderColumn = $this->name;

//        $this->gridTable->orderConfigure->getColumn($this->name)
//            ->setState($ordering)
//            ->setName($this->name)
//            ->setNextDirection('ASC');
//        $value = [
//            self::ORDERING_STATE          => $ordering,
//            self::ORDERING_NAME           => $this->name,
//            self::ORDERING_NEXT_DIRECTION => 'ASC',
//        ];
//        $this->globalConfigure->addConfigure(GridTable::GLOBAL_ORDER, $this->name, $value);
        return $this;
    }


    /**
     * Set ordering by.
     *
     * @param string $column
     * @return Column
     */
    public function setOrderingBy(string $column): self
    {
        $this->orderState = true;
        $this->orderColumn = $column;

//        $this->gridTable->orderConfigure->getColumn($this->name)
//            ->setState(true)
//            ->setName($column)
//            ->setNextDirection('ASC');
//        $value = [
//            self::ORDERING_STATE          => true,
//            self::ORDERING_NAME           => $column,
//            self::ORDERING_NEXT_DIRECTION => 'ASC',
//        ];
//        $this->globalConfigure->addConfigure(GridTable::GLOBAL_ORDER, $this->name, $value);
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
     * Set format dateTime.
     * Internal callback.
     *
     * @param string $format
     * @return Column
     */
    public function setFormatDateTime(string $format = 'Y-m-d H:i:s'): self
    {
        $this->configure[self::CALLBACK] = function ($data, Column $context) use ($format) {
            $value = $data[$context->getName()];
            if ($value) {
                if ($value instanceof DateInterval) {
                    return $value->format($format);
                } else {
                    return DateTime::from($value)->format($format);
                }
            }
            return null;
        };
        return $this;
    }


    /**
     * Set format boolean.
     * Internal callback.
     *
     * @return Column
     */
    public function setFormatBoolean(): self
    {
        $this->configure[self::CALLBACK] = function ($data, Column $context) {
            $value = (bool) $data[$context->getName()];
            return Html::el('input', ['type' => 'checkbox', 'disabled' => true, 'checked' => $value]);
        };
        return $this;
    }


    /**
     * Set format string.
     * Internal callback.
     *
     * @param string $format
     * @return Column
     */
    public function setFormatString(string $format): self
    {
        $this->configure[self::CALLBACK] = function ($data, Column $context) use ($format) {
            $value = $data[$context->getName()];
            if ($value) {
                return sprintf($format, $value);
            }
            return $value;
        };
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


//    /**
//     * Set filter.
//     *
//     * @param array|null $values
//     * @return Column
//     */
//    public function setFilter(array $values = null): self
//    {
//        $this->configure[self::FILTER] = $values ?? true;
//        return $this;
//    }
}
