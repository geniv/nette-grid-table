<?php declare(strict_types=1);

namespace GridTable;

use Nette\Application\IPresenter;
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

    /** @var string */
    private $caption, $confirm, $link, $permissionResource, $permissionPrivilege, $valueHtmlClass;

    /** @var array */
    private $linkArguments, $linkUrl, $valueData;

    /** @var callable */
    private $valueCallback;

    /**
     * Button constructor.
     *
     * @param string $caption
     */
    public function __construct(string $caption)
    {
        $this->caption = $caption;
    }
//TODO reformat!!

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
        if (isset($this->permissionResource) && isset($this->permissionPrivilege)) {
            /* @noinspection PhpUndefinedMethodInspection */
            return $presenter->getUser()->isAllowed($this->permissionResource, $this->permissionPrivilege);
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
        /* @noinspection ALL */
        $request = $presenter->request->getParameters();
        array_walk($request, function ($item, $key, $prefix) use (&$requestData) {
            $requestData[$prefix . '.' . $key] = $item; // add prefix to key in array
        }, 'request');

        // merge data and request data
        $data = array_merge((array)$data, $requestData);
        // call callback
        if (isset($this->valueCallback)) {
            $data = call_user_func($this->valueCallback, $data, $this);
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
        }, $this->linkArguments);
        // merge url after substitute
        if (isset($this->linkUrl)) {
            $arr = array_merge($arr, $this->linkUrl);
        }
        /* @noinspection PhpUndefinedMethodInspection */
        return $presenter->link($this->link, array_filter($arr));
    }

    /**
     * Get caption.
     *
     * @return string
     */
    public function getCaption(): string
    {
        return $this->caption;
    }

    /**
     * Get confirm.
     *
     * @return string
     */
    public function getConfirm(): string
    {
        return $this->confirm ?? '';
    }

    /**
     * Get class.
     *
     * @return string
     */
    public function getClass(): string
    {
        return $this->valueHtmlClass ?? '';
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
        $this->caption = $caption;
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
        $this->link = $link;
        $this->linkArguments = $arguments;
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
        $this->linkUrl = $arguments;
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
        $this->confirm = $text;
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
        $this->permissionResource = $resource;
        $this->permissionPrivilege = $privilege;
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
        $this->valueHtmlClass = $class;
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
        $this->valueData = $data;
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
        $this->valueCallback = $callback;
        return $this;
    }
}
