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

    const
        CAPTION = 'caption',
        CONFIRM = 'confirm',
        LINK = 'link',
        LINK_ARGUMENTS = 'link-arguments',
        PERMISSION_RESOURCE = 'permission_resource',
        PERMISSION_PRIVILEGE = 'permission_privilege';

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
        return $presenter->getUser()->isAllowed($this->configure[self::PERMISSION_RESOURCE], $this->configure[self::PERMISSION_PRIVILEGE]);
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
        $arr = (array_map(function ($row) use ($data) {
            return str_replace(array_keys((array) $data), (array) $data, $row);
        }, $this->configure[self::LINK_ARGUMENTS]));
        return $presenter->link($this->configure[self::LINK], $arr);
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


    /*
     * PHP
     */


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
     * Set confirm.
     *
     * @param $text
     * @return $this
     */
    public function setConfirm($text)
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
}
