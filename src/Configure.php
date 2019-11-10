<?php declare(strict_types=1);

namespace GridTable;

use Nette\SmartObject;


/**
 * Class Configure
 *
 * @deprecated
 * @author  geniv
 * @package GridTable
 */
class Configure
{
    use SmartObject;

    /** @var array */
    private $data = [];


    /**
     * Get configures.
     *
     * @return array
     */
    public function getConfigures(): array
    {
        return $this->data;
    }


    /**
     * Get configure.
     *
     * @param string $index
     * @param null   $default
     * @return mixed|null
     */
    public function getConfigure(string $index, $default = null)
    {
        return $this->data[$index] ?? $default;
    }


    /**
     * Set configure.
     *
     * @param string $name
     * @param        $value
     * @return Configure
     */
    public function setConfigure(string $name, $value): self
    {
        $this->data[$name] = $value;
        return $this;
    }


    /**
     * Add configure.
     *
     * @param string $index
     * @param string $name
     * @param        $value
     * @return Configure
     */
    public function addConfigure(string $index, string $name, $value): self
    {
        $this->data[$index][$name] = $value;
        return $this;
    }
}
