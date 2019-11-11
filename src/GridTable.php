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
    /** @var array */
    private $columns = [], $actions = [];

    /** @var ITranslator */
    private $translator = null;
    /** @var string */
    private $templatePath, $cacheId, $columnPk;

    /** @var bool */
    private $sortable = false;
    /** @var array */
    private $orderDefault = [];
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
    public
        /** @noinspection PhpUnused */
        $onColumnOrder,
        /** @noinspection PhpUnused */
        $onSelectPaginatorRange;    ///*$onSelectRow, $onSelectFilter,*/


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
        $this->cache = new Cache($storage, 'GridTable-GridTable');

        $this->templatePath = __DIR__ . '/GridTable.latte'; // path
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
        $columnId = implode(array_keys($this->columns));
        $listId = serialize(trim((string) $this->source));
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
        if (!$this->sortable) {
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
        $this->sortable = $state;
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
        return $this->sortable;
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
        $this->columnPk = $pk;
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
        $this->orderDefault = $order;
        return $this;
    }


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
        $this->actions[$caption] = $button;
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
        $this->columns[$name] = $column;
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
        // set next order direction
        $columns = $this->columns;
        if (isset($columns[$column])) {
            /* @noinspection PhpUndefinedMethodInspection */
            $columns[$column]->setOrder($direction);
        }

        // rewrite default order
        if ($direction) {
            /** @noinspection PhpUndefinedMethodInspection */
            $this->orderDefault = [$columns[$column]->getOrderColumn() => $direction];
        }

        $this->onColumnOrder($column, $direction);

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

        /** @noinspection PhpUndefinedMethodInspection */
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
        $order = $this->orderDefault;
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
        $template->pk = $this->columnPk;
        $template->columns = $this->columns;
        $template->action = $this->actions;
        $template->paginatorRange = $this->paginatorRange ?? [];
        $template->paginatorItemsPerPage = ($this->paginator ? $this->paginator->getItemsPerPage() : 10);

        /* @noinspection PhpUndefinedMethodInspection */
        $template->setTranslator($this->translator);
        $template->setFile($this->templatePath);
        $template->render();
    }
}
