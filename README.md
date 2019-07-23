Grid table
==========

inspired by: http://grid.mesour.com/version2/column/actions/

Installation
------------
```sh
$ composer require geniv/nette-grid-table
```
or
```json
"geniv/nette-grid-table": "^1.3"
```

require:
```json
"php": ">=7.0",
"nette/application": ">=2.4",
"nette/caching": ">=2.5",
"nette/component-model": ">=2.3",
"nette/utils": ">=2.4",
"geniv/nette-general-form": ">=1.0"
```

Include in application
----------------------
neon configure services:
```neon
services:
    - GridTable\GridTable
```

usage:
```php
protected function createComponentGridTable(GridTable $gridTable, VisualPaginator $visualPaginator): GridTable
{
    $visualPaginator->setPathTemplate(__DIR__ . '/templates/visualPaginator.latte');
    $gridTable->setPaginator($visualPaginator->getPaginator(), $visualPaginator);
    $gridTable->setItemPerPage($this->getDatabaseLimit());
//    $gridTable->setPage((int) 4);
//    $gridTable->setSortable(false);

    $gridTable->setTemplatePath(__DIR__ . '/templates/gridTable.latte');
    $gridTable->setSource($this->getSource());
//    $gridTable->setCacheId('123'.$neco);
//    $gridTable->setSource(new ArrayDataSource($this->configureSection->getListSection()));
//    $gridTable->setSource(new ApiDataSource(function ($limit, $offset) {
//        return $this->apiModel->getListApi($limit, $offset);
//    }, 'totalCount', 'result'));

    $pk = 'id';
    $gridTable->setPrimaryKey($pk);
    $gridTable->setDefaultOrder(['id' => 'asc']);

    $gridTable->addColumn($pk, '#');

    $column = $gridTable->addColumn('username', 'Jmeno');
    $column->setOrdering(true);
    $column->setData(['foo' => 'bar']);

//        $column->setCallback(function ($data, Column $context) { return $data; });
    $column->setCallback(function ($data) { return $data; });
   
    $column = $gridTable->addColumn('username', 'Jmeno');
    $column->setTemplatePath(__DIR__ . '/templates/gridTableCheckbox.latte');

    // edit
    $gridTable->addButton('content-grid-table-edit')
        ->setLink($this->presenterName . ':edit', ['idSection' => $this->idSection, 'id' => '%id', null])
        ->setClass('edit-class')
        ->setData(['svg' => self::SVG_USE_EDIT])
        ->setPermission($this->idSection, 'edit');
//        ->setData($configure);

    // delete
    $gridTable->addButton('content-grid-table-delete')
        ->setLink($this->presenterName . ':delete', ['idSection' => $this->idSection, 'id' => '%id'])
        ->setClass('btn btn-delete')
        ->setData(['svg' => self::SVG_USE_DELETE])
        ->setPermission($this->idSection, 'delete')
        ->setConfirm('content-grid-table-delete-confirm')
        ->setCallback(function (array $data, Button $context) { return $data; });

    return $gridTable;
}
```

##### Drivers:
- Dibi IDataSource instance (native)
- ArrayDataSource(array $data)
- FinderDataSource(Finder $finder)
- ApiDataSource(callable $function($limit, $offset){ return ApiCall($limit, $offset); }, 'totalCount', 'result')

##### class GridTable
```php
cleanCache($name = 'grid')
setTemplatePath(string $path)
setSource(IDataSource $source): self
setSourceLimit(int $limit, int $offset = 0): self
setItemPerPage(int $itemPerPage, bool $exception = false): self  - probably load data from session
setPage(int $page, bool $exception = false)
setPaginator(IComponent $visualPaginator = null, callable $callback = null): self
setPaginatorRange(array $range): self
setSortable(bool $state): self
isSortable(): bool
setPrimaryKey(string $pk): self
setDefaultOrder(array $order): self
setSelection(array $action): self
isSelection(): bool
setSelectRow(array $data)   - load data from session
setFilter(bool $state): self
isFilter(): bool
setSelectFilter(array $data): self  - load data from session
addButton(string $caption): Button
addColumn(string $name, string $header = null): Column

onSelectRow(array $array)
onColumnOrder(string $column, string|null $direction)
onSelectFilter(string $column, string $filter)
onSelectPaginatorRange(int $value)
```

##### class Column
```php
setOrdering(bool $ordering = true): self
setData(array $data): self
// internal variable: $column, $value, $data + custom over setData([])
setCallback(callable $callback): self  -  function ($data, Column $context) { return $data[$context->getName()]; }
setTemplatePath(string $path, array $data = []): self
setFormatDateTime(string $format): self
setFormatBoolean(): self
setFormatString(string $format): self
setFilter(array $values): self
```

##### class Button
```php
setCaption(string $caption): self
setLink(string $link, array $arguments = []): self
setUrl(array $arguments = []): self
setConfirm(string $text): self
setPermission(string $resource, string $privilege): self
setClass(string $class): self
setData(array $data): self
setCallback(callable $callback): self  -  function ($data, Button $context) { return $data[$context->getName()]; }
```

set page in external call:
```php
$this['gridTable']->setPage((int) $page);
```

usage:
```latte
<a n:if="$user->isAllowed(...)" n:href="add">add</a>
<br>
{control gridTable}
```

usage with `Multiplier`:
```php
public function createComponentGridTableMultiplier(GridTable $gridTable): Multiplier
{
    return new Multiplier(function ($index) use ($gridTable) {
        $gridTable = clone $gridTable;

        $source = clone $this->getSource();
        // ...

        return $gridTable;
    });
}
```
