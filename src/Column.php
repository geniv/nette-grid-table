<?php declare(strict_types=1);

namespace GridTable;

use DateInterval;
use GeneralForm\ITemplatePath;
use Nette\SmartObject;
use Nette\Utils\DateTime;
use Nette\Utils\Html;


/**
 * Class Column
 *
 * @author  geniv
 * @package GridTable
 */
class Column implements ITemplatePath
{
    use SmartObject;

    /** @var string */
    private $name, $header, $orderColumn, $orderCurrentDirection = '', $orderNextDirection = 'ASC';
    /** @var bool */
    private $orderState = false;

    private $valueData;
    /** @var callable */
    private $valueCallback;
    /** @var string */
    private $valueTemplate;
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
    }


    /**
     * Get order column.
     *
     * @noinspection PhpUnused
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


    /**
     * Is ordering.
     *
     * @noinspection PhpUnused
     * @return bool
     */
    public function isOrdering(): bool
    {
        return $this->orderState;
    }


    /**
     * Get order href.
     *
     * @noinspection PhpUnused
     * @return array
     */
    public function getOrderHref(): array
    {
        return [$this->name, $this->orderNextDirection];
    }


    /**
     * Get current order.
     *
     * @noinspection PhpUnused
     * @return string
     */
    public function getCurrentOrder(): string
    {
        return $this->orderCurrentDirection ?? '';
    }


    /**
     * Get data.
     *
     * @param string|null $index
     * @return mixed|null
     */
    public function getData(string $index = null)
    {
        $data = $this->valueData ?? null;
        return ($index ? ($data[$index] ?? null) : $data);
    }


    /**
     * Get value.
     *
     * @noinspection PhpUnused
     * @param $data
     * @return string
     */
    public function getValue($data): string
    {
        $value = (isset($this->valueCallback) ? call_user_func($this->valueCallback, $data, $this) : $data[$this->name]);
        if (isset($this->valueTemplate)) {
            $template = $this->gridTable->getTemplate();
            /** @noinspection PhpUndefinedFieldInspection */
            $template->column = $this;
            /** @noinspection PhpUndefinedFieldInspection */
            $template->value = $value;
            /** @noinspection PhpUndefinedFieldInspection */
            $template->data = $data;
            foreach ($this->getData() ?? [] as $key => $val) {
                $template->$key = $val;
            }
            $template->setFile($this->valueTemplate);
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
     * @noinspection PhpUnused
     * @param bool $state
     * @return Column
     */
    public function setOrdering(bool $state = true): self
    {
        $this->orderState = $state;
        $this->orderColumn = $this->name;
        return $this;
    }


    /**
     * Set ordering by.
     *
     * @noinspection PhpUnused
     * @param string $column
     * @return Column
     */
    public function setOrderingBy(string $column): self
    {
        $this->orderState = true;
        $this->orderColumn = $column;
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
        $this->valueData = $data;
        return $this;
    }


    /**
     * Set callback.
     *
     * @noinspection PhpUnused
     * @param callable $callback
     * @return Column
     */
    public function setCallback(callable $callback): self
    {
        $this->valueCallback = $callback;
        return $this;
    }


    /**
     * Set format dateTime.
     * Internal callback.
     *
     * @noinspection PhpUnused
     * @param string $format
     * @return Column
     */
    public function setFormatDateTime(string $format = 'Y-m-d H:i:s'): self
    {
        $this->valueCallback = function ($data, Column $context) use ($format) {
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
     * @noinspection PhpUnused
     * @return Column
     */
    public function setFormatBoolean(): self
    {
        $this->valueCallback = function ($data, Column $context) {
            $value = (bool) $data[$context->getName()];
            return Html::el('input', ['type' => 'checkbox', 'disabled' => true, 'checked' => $value]);
        };
        return $this;
    }


    /**
     * Set format string.
     * Internal callback.
     *
     * @noinspection PhpUnused
     * @param string $format
     * @return Column
     */
    public function setFormatString(string $format): self
    {
        $this->valueCallback = function ($data, Column $context) use ($format) {
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
        $this->valueTemplate = $path;
        if ($data) {
            $this->setData(array_merge($this->valueData ?? [], $data));
        }
        return $this;
    }
}
