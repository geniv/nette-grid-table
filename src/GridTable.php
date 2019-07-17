<?php declare(strict_types=1);

namespace GridTable;

use Exception;
use GeneralForm\ITemplatePath;
use GridTable\Drivers\IDataSource;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nette\Caching\Cache;
use Nette\Caching\IStorage;
use Nette\ComponentModel\IComponent;
use Nette\Localization\ITranslator;
use Nette\Utils\Paginator;
use stdClass;


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
        CONFIGURE_SORTABLE = 'sortable',

        COLUMN = 'column',
        ACTION = 'action';

    /** @var ITranslator */
    private $translator = null;
    /** @var string */
    private $templatePath, $cacheId;
    /** @var Configure */
    private $configure;
    /** @var IDataSource */
    private $source;
    /** @var array */
    private $sourceLimit;
    /** @var Cache */
    private $cache;
    /** @var Paginator */
    private $paginator = null;


    /**
     * GridTable constructor.
     *
     * @param IStorage         $storage
     * @param ITranslator|null $translator
     */
    public function __construct(IStorage $storage, ITranslator $translator = null)
    {
        parent::__construct();

        $this->translator = $translator;

        $this->configure = new Configure();
        $this->cache = new Cache($storage, 'GridTable-GridTable');

        $this->templatePath = __DIR__ . '/GridTable.latte'; // path
    }


    /**
     * Get configure.
     *
     * @return Configure
     * @internal
     */
    public function getConfigure(): Configure
    {
        return $this->configure;
    }


    /**
     * Get cache id.
     *
     * @return string
     * @internal
     */
    private function getCacheId()
    {
        // internal usage for inner-cache in latte
        $columnId = implode(array_keys($this->configure->getConfigure(self::COLUMN, [])));
        $listId = serialize(trim((string) $this->source));
        return $columnId . $listId . $this->cacheId;
    }


    /**
     * Clean cache.
     *
     * @param string $name
     */
    public function cleanCache($name = 'grid')
    {
        $this->cache->clean([Cache::TAGS => [$name]]);   // internal clean cache for grid
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
     * @param IDataSource $source
     * @return GridTable
     */
    public function setSource($source): self
    {
        $this->source = $source;
        return $this;
    }


    /**
     * Set source limit.
     * Default limit and offset for usage without paginator.
     *
     * @param int $limit
     * @param int $offset
     * @return GridTable
     */
    public function setSourceLimit(int $limit, int $offset = 0): self
    {
        $this->sourceLimit = ['limit' => $limit, 'offset' => $offset];
        return $this;
    }


    /**
     * Set cache id.
     * Set additional custom cache id (because setSource can have external parameters)
     *
     * @param string $cacheId
     * @return GridTable
     */
    public function setCacheId(string $cacheId): self
    {
        $this->cacheId = $cacheId;
        return $this;
    }


    /**
     * Set item per page.
     *
     * @param int  $itemPerPage
     * @param bool $exception
     * @throws Exception
     */
    public function setItemPerPage(int $itemPerPage, bool $exception = false)
    {
        if ($this->paginator) {
            $this->paginator->setItemsPerPage($itemPerPage);
        } else {
            if ($exception) {
                throw new Exception('Visual paginator is not define!');
            }
        }
    }


    /**
     * Set page.
     *
     * @param int  $page
     * @param bool $exception
     * @throws Exception
     */
    public function setPage(int $page, bool $exception = false)
    {
        if ($this->paginator) {
            $this->paginator->setPage($page);
        } else {
            if ($exception) {
                throw new Exception('Visual paginator is not define!');
            }
        }
    }


    /**
     * Set paginator.
     *
     * @param IComponent|null $visualPaginator
     * @param callable|null   $callback
     */
    public function setPaginator(IComponent $visualPaginator = null, callable $callback = null)
    {
        // disable pagination for sortable
        if (!$this->configure->getConfigure(self::CONFIGURE_SORTABLE, false)) {
            if (!$callback) {
                // default paginator component usage VisualPaginator
                /* @noinspection PhpUndefinedMethodInspection */
                $this->paginator = $visualPaginator->getPaginator();
            } else {
                $this->paginator = $callback($visualPaginator);
            }

            if ($visualPaginator) {
                $this->addComponent($visualPaginator, 'visualPaginator');
            }
        }
    }


    /**
     * Set sortable.
     * Ajax sortable items.
     *
     * @param bool $state
     * @return GridTable
     */
    public function setSortable(bool $state): self
    {
        // disable pagination for all items
        $this->configure->setConfigure(self::CONFIGURE_SORTABLE, $state);
        return $this;
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
     * Set selection.
     * Row selection.
     *
     * @param array $action
     * @return GridTable
     */
    public function setSelection(array $action): self
    {
        //TODO musi chytat nejaky JS plugin ktery umozni nejakou grafiku - uvladani musi ukladat do session - ale venkovnim callbyckem a pro ovladani session protoze strankvoani!
        // a pak podle vybranych chekboxu vyresit na jakou metodu se data predhodi!!!
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
        $column = new Column($this, $name, $header);
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
//        \Tracy\Debugger::fireLog('handleColumnOrder:: ' . $column . '-' . $direction);
        // set next order direction
        $columns = $this->configure->getConfigure(self::COLUMN);
        if (isset($columns[$column])) {
            /* @noinspection PhpUndefinedMethodInspection */
            $columns[$column]->setOrder($direction);
        }

        // set default order
        if ($direction) {
            $this->configure->setConfigure(self::CONFIGURE_ORDER, [$column => $direction]);
        }

        // redraw snippet
        if ($this->presenter->isAjax()) {
            $this->cache->clean([Cache::TAGS => ['grid']]); // clean tag for order, need call setOrder!!
            $this->redrawControl('grid');
        }
    }


    /**
     * Render.
     *
     * @throws Exception
     */
    public function render()
    {
        $template = $this->getTemplate();

        if (!$this->source) {
            throw new Exception('Source is not define!');
        }

        // ordering
        $order = $this->configure->getConfigure(self::CONFIGURE_ORDER);
        if ($order) {
            /* @noinspection PhpUndefinedMethodInspection */
            $this->source->orderBy($order);
        }

        if ($this->paginator) {
            // set visual paginator
            $this->paginator->setItemCount(count($this->source));   // call count()
            /* @noinspection PhpUndefinedMethodInspection */
            $this->source->limit($this->paginator->getLength())->offset($this->paginator->getOffset());
        } else {
            if ($this->sourceLimit) {
                /* @noinspection PhpUndefinedMethodInspection */
                $this->source->limit($this->sourceLimit['limit'])->offset($this->sourceLimit['offset']);
            }
        }

        /** @var stdClass $template */
        $template->list = $this->source->getIterator(); // call getIterator() -- first (build data)
        $template->cacheId = $this->getCacheId();   // for inner-cache; call __toString() -- second (use serialize build data)
        $template->configure = $this->configure->getConfigures();
        $template->columns = $this->configure->getConfigure(self::COLUMN, []);
        $template->action = $this->configure->getConfigure(self::ACTION, []);

//        dump($template->configure);
//        dump($template->columns);

        /* @noinspection PhpUndefinedMethodInspection */
        $template->setTranslator($this->translator);
        $template->setFile($this->templatePath);
        $template->render();
    }
}
