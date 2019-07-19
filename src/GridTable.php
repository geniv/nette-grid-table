<?php declare(strict_types=1);

namespace GridTable;

use Exception;
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
 * @method onSelectRow(array $array)
 * @method onColumnOrder(string $column, string|null $direction)
 * @method onSelectFilter(string $column, string $filter)
 * @method onSelectPaginatorRange(int $value)
 */
class GridTable extends Control implements ITemplatePath
{
    const
        CONFIGURE_PK = 'pk',
        CONFIGURE_ORDER = 'order',
        CONFIGURE_SORTABLE = 'sortable',
        CONFIGURE_SELECTION = 'selection',

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
    /** @var array */
    private $paginatorRange;
    /** @var array */
    private $selectRow = [], $selectFilter = [];
    /** @var callable */
    public $onColumnOrder, $onSelectRow, $onSelectFilter, $onSelectPaginatorRange;


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
        $listId = serialize(trim((string) $this->source)) . serialize($this->selectRow);
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
     * Set paginator range.
     *
     * @param array $range
     */
    public function setPaginatorRange(array $range)
    {
        $this->paginatorRange = $range;
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
        $this->configure->setConfigure(self::CONFIGURE_ORDER, $order);
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

        $this->configure->setConfigure(self::CONFIGURE_SELECTION, $action);

        return $this;
    }


    /**
     * Set select row.
     *
     * @param array $data
     */
    public function setSelectRow(array $data)
    {
        $this->selectRow = $data;
    }


    /**
     * Handle selection all row.
     *
     * @param bool $state
     */
    public function handleSelectionAllRow(bool $state)
    {
        $list = $this->getList();
        $pk = $this->configure->getConfigure(self::CONFIGURE_PK);
        foreach ($list as $item) {
            $this->selectRow[$item[$pk]] = $state;
        }
        $this->onSelectRow($this->selectRow);

        // redraw snippet
        $this->cleanCache();
    }

//TODO doresti zobrazovani akci selekce
//TODO doresti oznacivani uplne vseho a odznacovani uplne vseho!!
//TODO filtry!
//TODO pokud se nedefinuje obsah rak si udela grupu z danehe sloupce + to zanese do cache
// tento vyber se taky posype do session pres externi callback volani!!!

    /**
     * Handle selection row.
     *
     * @param int  $id
     * @param bool $state
     */
    public function handleSelectionRow(int $id, bool $state)
    {
        $this->selectRow[$id] = $state;

        $this->onSelectRow($this->selectRow);

        // redraw snippet
        $this->cleanCache();
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

        $this->onColumnOrder($column, $direction);

        // redraw snippet
        $this->cleanCache();
    }


    /**
     * Handle select filter.
     *
     * @param string $column
     * @param string $filter
     */
    public function handleSelectFilter(string $column, string $filter)
    {
        $this->selectFilter[$column] = $filter;

        $this->onSelectFilter($column, $filter);

        // redraw snippet
        $this->cleanCache();
    }


    /**
     * Handle select paginator range.
     *
     * @param int $value
     */
    public function handleSelectPaginatorRange(int $value)
    {
        $this->onSelectPaginatorRange($value);

        // redraw snippet
        $this->cleanCache();
    }


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

        /** @var stdClass $template */
        $template->list = $this->getList();
        $template->cacheId = $this->getCacheId();   // for inner-cache; call __toString() -- second (use serialize build data)
        $template->configure = $this->configure->getConfigures();
        $template->columns = $this->configure->getConfigure(self::COLUMN, []);
        $template->action = $this->configure->getConfigure(self::ACTION, []);
        $template->selectRow = $this->selectRow ?? [];
        $template->selectRow = $this->selectFilter ?? [];   //TODO aplikovat
        $template->paginatorRange = $this->paginatorRange;  //TODO aplikovat


//        $filter = [];
//        foreach ($template->list as $item) {
//            foreach ($template->columns as $column) {
////                dump($template->list);
//                if ($column->isFilter()) {
//                    $filter[$column->getName()][] = $item[$column->getName()];
//                }
//            }
//        }
//        dump($filter);


//        dump($template->configure);
//        dump($template->columns);

        /* @noinspection PhpUndefinedMethodInspection */
        $template->setTranslator($this->translator);
        $template->setFile($this->templatePath);
        $template->render();
    }
}
