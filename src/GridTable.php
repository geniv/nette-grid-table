<?php declare(strict_types=1);

namespace GridTable;

use GeneralForm\ITemplatePath;
use GridTable\Drivers\IDataSource;
use Nette\Application\UI\Control;
use Nette\Caching\Cache;
use Nette\Caching\IStorage;
use Nette\ComponentModel\IComponent;
use Nette\Localization\ITranslator;
use Nette\Utils\Paginator;
use stdClass;
use Traversable;


/**
 * Class GridTable
 *
 * @author  geniv
 * @package GridTable
 * @method onColumnOrder(string $column, string|null $direction)
 * @method onSelectPaginatorRange(int $value)
 */
class GridTable extends Control implements ITemplatePath
{
    const
        CONFIGURE_PK = 'pk',
        CONFIGURE_ORDER = 'order',
        CONFIGURE_SORTABLE = 'sortable',
//        GLOBAL_ORDER = 'global-order',
//        CONFIGURE_SELECTION = 'selection',
//        CONFIGURE_FILTER = 'filter',

        COLUMN = 'column',
        ACTION = 'action';

    /** @var ITranslator */
    private $translator = null;
    /** @var string */
    private $templatePath, $cacheId;
    /** @var Configure */
    private $configure;
    /** @var OrderConfigure */
    public $orderConfigure;
    /** @var IDataSource */
    private $source;
    /** @var array */
    private $sourceLimit;
    /** @var Cache */
    private $cache;
    /** @var Paginator */
    private $paginator = null;
    /** @var array */
    private $paginatorRange = []; //, $selectRow = [], $selectFilter = [];
    /** @var callable */
    public $onColumnOrder, $onSelectPaginatorRange;    ///*$onSelectRow, $onSelectFilter,*/


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
        $this->orderConfigure = new OrderConfigure();
        $this->cache = new Cache($storage, 'GridTable-GridTable');

        $this->templatePath = __DIR__ . '/GridTable.latte'; // path
    }


    /**
     * Get configure.
     *
     * @noinspection PhpUnused
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
        $listId = serialize(trim((string) $this->source));// . serialize($this->selectRow);
        return $columnId . $listId . $this->cacheId;
    }


    /**
     * Clean cache.
     *
     * @param string $name
     * @param bool   $redraw
     */
    public function cleanCache(string $name = 'grid', bool $redraw = true)
    {
        if ($name) {
            $this->cache->clean([Cache::TAGS => [$name]]);   // internal clean cache for grid
        }

        if ($this->presenter && $redraw) {
            if ($this->presenter->isAjax()) {
                $this->redrawControl('grid');
            }
        }
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
     * @noinspection PhpUnused
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
     * @noinspection PhpUnused
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
     * @noinspection PhpUnused
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
     * @noinspection PhpUnused
     * @param int  $itemPerPage
     * @param bool $exception
     * @return GridTable
     * @throws GridTableException
     */
    public function setItemPerPage(int $itemPerPage, bool $exception = false): self
    {
        if ($this->paginator) {
            $this->paginator->setItemsPerPage($itemPerPage);
        } else {
            if ($exception) {
                throw new GridTableException('Visual paginator is not define!');
            }
        }
        return $this;
    }


    /**
     * Set page.
     *
     * @noinspection PhpUnused
     * @param int  $page
     * @param bool $exception
     * @throws GridTableException
     */
    public function setPage(int $page, bool $exception = false)
    {
        if ($this->paginator) {
            $this->paginator->setPage($page);
        } else {
            if ($exception) {
                throw new GridTableException('Visual paginator is not define!');
            }
        }
    }


    /**
     * Set paginator.
     *
     * @noinspection PhpUnused
     * @param IComponent|null $visualPaginator
     * @param callable|null   $callback
     * @return GridTable
     */
    public function setPaginator(IComponent $visualPaginator = null, callable $callback = null): self
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
        return $this;
    }


    /**
     * Set paginator range.
     *
     * @noinspection PhpUnused
     * @param array $range
     * @return GridTable
     */
    public function setPaginatorRange(array $range): self
    {
        $this->paginatorRange = $range;
        return $this;
    }


    /**
     * Handle select paginator range.
     *
     * @noinspection PhpUnused
     * @param int $value
     */
    public function handleSelectPaginatorRange(int $value)
    {
        $this->onSelectPaginatorRange($value);

        // redraw snippet
        $this->cleanCache();
    }


    /**
     * Set sortable.
     * Ajax sortable items.
     *
     * @noinspection PhpUnused
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
     * Is sortable.
     *
     * @noinspection PhpUnused
     * @return bool
     */
    public function isSortable(): bool
    {
        return (bool) $this->configure->getConfigure(self::CONFIGURE_SORTABLE, false);
    }


    /**
     * Set primary key.
     *
     * @noinspection PhpUnused
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
     * @noinspection PhpUnused
     * @param array $order
     * @return GridTable
     */
    public function setDefaultOrder(array $order): self
    {
        $this->configure->setConfigure(self::CONFIGURE_ORDER, $order);
        return $this;
    }


//    /**
//     * Set selection.
//     * Turn on selection.
//     *
//     * @noinspection PhpUnused
//     * @param array $action
//     * @return GridTable
//     * @deprecated
//     */
//    public function setSelection(array $action): self
//    {
//        $this->configure->setConfigure(self::CONFIGURE_SELECTION, $action);
//        return $this;
//    }


//    /**
//     * Is selection.
//     *
//     * @noinspection PhpUnused
//     * @return bool
//     * @deprecated
//     */
//    public function isSelection(): bool
//    {
//        return (bool) $this->configure->getConfigure(self::CONFIGURE_SELECTION, false);
//    }


//    /**
//     * Set select row.
//     * Load data from session.
//     *
//     * @noinspection PhpUnused
//     * @param array $data
//     * @deprecated
//     */
//    public function setSelectRow(array $data)
//    {
//        $this->selectRow = $data;
//    }


//    /**
//     * Handle selection all row.
//     *
//     * @noinspection PhpUnused
//     * @param bool $state
//     * @deprecated
//     */
//    public function handleSelectionAllRow(bool $state)
//    {
//        $list = $this->getList();
//        $pk = $this->configure->getConfigure(self::CONFIGURE_PK);
//        foreach ($list as $item) {
//            $this->selectRow[$item[$pk]] = $state;
//        }
//        $this->onSelectRow($this->selectRow);
//
//        // redraw snippet
//        $this->cleanCache();
//    }


//    /**
//     * Handle selection row.
//     *
//     * @noinspection PhpUnused
//     * @param int  $id
//     * @param bool $state
//     * @deprecated
//     */
//    public function handleSelectionRow(int $id, bool $state)
//    {
//        $this->selectRow[$id] = $state;
//
//        $this->onSelectRow($this->selectRow);
//
//        // redraw snippet
//        $this->cleanCache();
//    }


    /**
     * Add button.
     *
     * @noinspection PhpUnused
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
     * @noinspection PhpUnused
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
     * @noinspection PhpUnused
     * @param string      $column
     * @param string|null $direction
     */
    public function handleColumnOrder(string $column, string $direction = null)
    {
//        \Tracy\Debugger::fireLog('handleColumnOrder:: ' . $column . '-' . $direction);


//        bdump($this->orderConfigure->getColumn($column));


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

        $this->onColumnOrder($column, $direction);

        // redraw snippet
        $this->cleanCache();
    }


//    /**
//     * Set filter.
//     * Turn on filter.
//     *
//     * @noinspection PhpUnused
//     * @param bool $state
//     * @return GridTable
//     * @deprecated
//     */
//    public function setFilter(bool $state): self
//    {
//        $this->configure->setConfigure(self::CONFIGURE_FILTER, $state);
//        return $this;
//    }


//    /**
//     * Is select filter.
//     *
//     * @noinspection PhpUnused
//     * @return bool
//     * @deprecated
//     */
//    public function isFilter(): bool
//    {
//        return (bool) $this->configure->getConfigure(self::CONFIGURE_FILTER, false);
//    }


//    /**
//     * Set select filter.
//     * Load data from session.
//     *
//     * @noinspection PhpUnused
//     * @param array $data
//     * @return GridTable
//     * @deprecated
//     */
//    public function setSelectFilter(array $data): self
//    {
//        $this->selectFilter = $data;
//
//        //TODO pokud se nedefinuje obsah tak si udela grupu z danehe sloupce + to zanese do cache
//        // tento vyber se taky posype do session pres externi callback volani!!!
//        return $this;
//    }


//    /**
//     * Handle select filter.
//     *
//     * @noinspection PhpUnused
//     * @param string $column
//     * @param string $filter
//     * @param bool   $state
//     * @deprecated
//     */
//    public function handleSelectFilter(string $column, string $filter, bool $state)
//    {
//        $this->selectFilter[$column][$filter] = $state;
//
//        $this->onSelectFilter($this->selectFilter);
//
//        // redraw snippet
//        $this->cleanCache();
//    }


    /**
     * Get list.
     *
     * @return Traversable
     */
    private function getList(): Traversable
    {
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

        return $this->source->getIterator(); // call getIterator() -- first (build data)
    }


    /**
     * Render.
     *
     * @throws GridTableException
     */
    public function render()
    {
        $template = $this->getTemplate();

        if (!$this->source) {
            throw new GridTableException('Source is not define!');
        }

        // ordering
        $order = $this->configure->getConfigure(self::CONFIGURE_ORDER);
        if ($order) {
            // search natural order
            $natural = array_filter($order, function ($item) {
                return (strpos($item, '+') !== false);
            });

            if ($natural) {
                foreach ($order as $item) {
                    /* @noinspection PhpUndefinedMethodInspection */
                    $this->source->orderBy($item);
                }
            } else {
                /* @noinspection PhpUndefinedMethodInspection */
                $this->source->orderBy($order);
            }
        }

        /** @var stdClass $template */
        $template->list = $this->getList();
        $template->cacheId = $this->getCacheId();   // for inner-cache; call __toString() -- second (use serialize build data)
        $template->configure = $this->configure->getConfigures();
        $template->columns = $this->configure->getConfigure(self::COLUMN, []);
        $template->action = $this->configure->getConfigure(self::ACTION, []);
//        $template->selectRow = $this->selectRow ?? [];
//        $template->selectFilter = $this->selectFilter ?? [];
        $template->paginatorRange = $this->paginatorRange ?? [];
        $template->paginatorItemsPerPage = ($this->paginator ? $this->paginator->getItemsPerPage() : 10);

//        dump($template->configure);
//        dump($template->columns);

        /* @noinspection PhpUndefinedMethodInspection */
        $template->setTranslator($this->translator);
        $template->setFile($this->templatePath);
        $template->render();
    }
}
