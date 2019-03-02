<?php declare(strict_types=1);

namespace GridTable;

use Nette\Application\IPresenter;
use Nette\Application\Request;
use Nette\Application\UI\Presenter;
use Nette\SmartObject;


/**
 * Class Button
 *
 * @author  geniv
 * @package GridTable
 */
class Button
{
    use SmartObject;

    const
        CAPTION = 'caption',
        CONFIRM = 'confirm',
        LINK = 'link',
        LINK_ARGUMENTS = 'link-arguments',
        LINK_URL = 'link-url',
        PERMISSION_RESOURCE = 'permission_resource',
        PERMISSION_PRIVILEGE = 'permission_privilege',
        HTML_CLASS = 'class',
        DATA = 'data',
        CALLBACK = 'callback';

    /** @var array */
    private $configure = [];


    /**
     * Button constructor.
     *
     * @param string $caption
     */
    public function __construct(string $caption)
    {
        $this->configure[self::CAPTION] = $caption;
    }


    /*
     * LATTE
     */


    /**
     * Is allowed.
     *
     * @param IPresenter $presenter
     * @return bool
     */
    public function isAllowed(IPresenter $presenter): bool
    {
        // use user acl
        if (isset($this->configure[self::PERMISSION_RESOURCE]) && isset($this->configure[self::PERMISSION_PRIVILEGE])) {
            return $presenter->getUser()->isAllowed($this->configure[self::PERMISSION_RESOURCE], $this->configure[self::PERMISSION_PRIVILEGE]);
        }
        return true;
    }


    /**
     * Get href.
     *
     * @param IPresenter $presenter
     * @param            $data
     * @return string
     */
    public function getHref(IPresenter $presenter, $data): string
    {
        // load request data from presenter
        $requestData = [];
        $request = $presenter->request->getParameters();
        array_walk($request, function ($item, $key, $prefix) use (&$requestData) {
            $requestData[$prefix . '.' . $key] = $item; // add prefix to key in array
        }, 'request');

        // merge data and request data
        $data = array_merge((array) $data, $requestData);
        // call callback
        if (isset($this->configure[self::CALLBACK])) {
            $data = $this->configure[self::CALLBACK]($data, $this);
        }
        $arr = array_map(function ($row) use ($data) {
            if ($row && $row[0] == '%') {
                // detect request data
                $index = substr($row, 1);
                if (isset($data[$index])) {
                    $row = $data[$index];
                } else {
                    $row = null;
                }
            }
            return $row;
        }, $this->configure[self::LINK_ARGUMENTS]);
        // merge url after substitute
        if (isset($this->configure[self::LINK_URL])) {
            $arr = array_merge($arr, $this->configure[self::LINK_URL]);
        }
        return $presenter->link($this->configure[self::LINK], array_filter($arr));
    }


    /**
     * Get caption.
     *
     * @return string
     */
    public function getCaption(): string
    {
        return $this->configure[self::CAPTION];
    }


    /**
     * Get confirm.
     *
     * @return string
     */
    public function getConfirm(): string
    {
        return $this->configure[self::CONFIRM] ?? '';
    }


    /**
     * Get class.
     *
     * @return string
     */
    public function getClass(): string
    {
        return $this->configure[self::HTML_CLASS] ?? '';
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


    /*
     * PHP
     */


    /**
     * Set caption.
     *
     * @param string $caption
     * @return Button
     */
    public function setCaption(string $caption): self
    {
        $this->configure[self::CAPTION] = $caption;
        return $this;
    }


    /**
     * Set link.
     *
     * @param string $link
     * @param array  $arguments
     * @return Button
     */
    public function setLink(string $link, array $arguments = []): self
    {
        $this->configure[self::LINK] = $link;
        $this->configure[self::LINK_ARGUMENTS] = $arguments;
        return $this;
    }


    /**
     * Set url.
     *
     * @param array $arguments
     * @return Button
     */
    public function setUrl(array $arguments = []): self
    {
        $this->configure[self::LINK_URL] = $arguments;
        return $this;
    }


    /**
     * Set confirm.
     *
     * @param string $text
     * @return Button
     */
    public function setConfirm(string $text): self
    {
        $this->configure[self::CONFIRM] = $text;
        return $this;
    }


    /**
     * Set permission.
     *
     * @param string $resource
     * @param string $privilege
     * @return Button
     */
    public function setPermission(string $resource, string $privilege): self
    {
        $this->configure[self::PERMISSION_RESOURCE] = $resource;
        $this->configure[self::PERMISSION_PRIVILEGE] = $privilege;
        return $this;
    }


    /**
     * Set class.
     *
     * @param string $class
     * @return Button
     */
    public function setClass(string $class): self
    {
        $this->configure[self::HTML_CLASS] = $class;
        return $this;
    }


    /**
     * Set data.
     *
     * @param array $data
     * @return Button
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
     * @return Button
     */
    public function setCallback(callable $callback): self
    {
        $this->configure[self::CALLBACK] = $callback;
        return $this;
    }
}
