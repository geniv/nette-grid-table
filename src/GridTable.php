<?php declare(strict_types=1);

namespace GridTable;

use Dibi\Fluent;
use GeneralForm\ITemplatePath;
use Nette\Application\UI\Control;
use Nette\ComponentModel\IComponent;
use Nette\Localization\ITranslator;


/**
 * Class GridTable
 *
 * @author  geniv
 * @package GridTable
 */
class GridTable extends Control implements ITemplatePath
{
    const
        CONFIGURE_PK = 'pk',
        CONFIGURE_ORDER = 'order',

        COLUMN = 'column',
        ACTION = 'action';

    /** @var ITranslator */
    private $translator = null;
    /** @var string */
    private $templatePath;
    /** @var Configure */
    private $configure;
    /** @var Fluent */
    private $source;


    /**
     * GridTable constructor.
     *
     * @param ITranslator|null $translator
     */
    public function __construct(ITranslator $translator = null)
    {
        parent::__construct();

        $this->translator = $translator;

        $this->configure = new Configure();

        $this->templatePath = __DIR__ . '/GridTable.latte'; // path
    }


    /**
     * Get configure.
     *
     * @return Configure
     */
    public function getConfigure(): Configure
    {
        return $this->configure;
    }


    /*
     * Global configure (one time)
     */


    /**
     * Set template path.
     *
     * @param string $path
     */
    public function setTemplatePath(string $path)
    {
        $this->templatePath = $path;
    }


    /**
     * Set source.
     *
     * @param Fluent $source
     * @return GridTable
     */
    public function setSource(Fluent $source): self
    {
        $this->source = $source;
        return $this;
    }


    /**
     * Set item per page.
     *
     * @param int $itemPerPage
     * @throws \Exception
     */
    public function setItemPerPage(int $itemPerPage)
    {
        if (isset($this['visualPaginator'])) {
            $this['visualPaginator']->getPaginator()->setItemsPerPage($itemPerPage);
        } else {
            throw new \Exception('Visual paginator is not define!');
        }
    }


    /**
     * Set page.
     *
     * @param int $page
     * @throws \Exception
     */
    public function setPage(int $page)
    {
        if (isset($this['visualPaginator'])) {
            $this['visualPaginator']->getPaginator()->setPage($page);
        } else {
            throw new \Exception('Visual paginator is not define!');
        }
    }


    /**
     * Set visual paginator.
     *
     * @param IComponent $component
     */
    public function setVisualPaginator(IComponent $component)
    {
        $this->addComponent($component, 'visualPaginator');
    }


    /**
     * Set primary key.
     *
     * @param string $pk
     * @return GridTable
     */
    public function setPrimaryKey(string $pk): self
    {
        $this->configure->setConfigure(self::CONFIGURE_PK, $pk);
        return $this;
    }


//    public function setMultipleSelect(bool $enable): self
//    {
//        //TODO konektor na multiselectivni mazani polozek pres checkboxy
//        return $this;
//    }


    /**
     * Set default order.
     *
     * @param array $order
     * @return GridTable
     */
    public function setDefaultOrder(array $order): self
    {
        if ($order) {
            $this->configure->setConfigure(self::CONFIGURE_ORDER, $order);
        }
        return $this;
    }


    /**
     * Add button.
     *
     * @param string $caption
     * @return Button
     */
    public function addButton(string $caption): Button
    {
        $button = new Button($caption);
        $this->configure->addConfigure(self::ACTION, $caption, $button);
        return $button;
    }


    /**
     * Add column.
     *
     * @param string      $name
     * @param string|null $header
     * @return Column
     */
    public function addColumn(string $name, string $header = null): Column
    {
        $column = new Column($name, $header);
        $this->configure->addConfigure(self::COLUMN, $name, $column);
        return $column;
    }


    /**
     * Handle column order.
     *
     * @param string      $column
     * @param string|null $direction
     */
    public function handleColumnOrder(string $column, string $direction = null)
    {
        // set next order direction
        $columns = $this->configure->getConfigure(self::COLUMN);
        if (isset($columns[$column])) {
            $columns[$column]->setOrder($direction);
        }

        // set default order
        if ($direction) {
            $this->configure->setConfigure(self::CONFIGURE_ORDER, [$column => $direction]);
        }

        // redraw snippet
        if ($this->presenter->isAjax()) {
            $this->redrawControl('grid');
        }
    }


    /**
     * Render.
     *
     * @throws \Exception
     */
    public function render()
    {
        $template = $this->getTemplate();

        if (!$this->source) {
            throw new \Exception('Source is not define!');
        }

        // ordering
        $order = $this->configure->getConfigure(self::CONFIGURE_ORDER);
        if ($order) {
            $this->source->orderBy($order);
        }

        if (isset($this['visualPaginator'])) {
            // set visual paginator
            $vp = $this['visualPaginator']->getPaginator();
            $vp->setItemCount(count($this->source));
            $this->source->limit($vp->getLength())->offset($vp->getOffset());
        }

        $template->list = $this->source;
        $template->configure = $this->configure->getConfigures();
        $template->columns = $this->configure->getConfigure(self::COLUMN, []);
        $template->action = $this->configure->getConfigure(self::ACTION);

//        dump($template->configure);
//        dump($template->columns);

        $template->setTranslator($this->translator);
        $template->setFile($this->templatePath);
        $template->render();
    }
}
