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
"geniv/nette-grid-table": "^1.2"
```

require:
```json
"php": ">=7.0",
"nette/application": ">=2.4",
"nette/caching": ">=2.5",
"nette/component-model": ">=2.3",
"nette/utils": ">=2.4",
"dibi/dibi": ">=3.0",
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
    $gridTable->setItemPerPage($this->wrapperSection->getDatabaseLimit());
//    $gridTable->setPage((int) 4);
//    $gridTable->setSortable(false);

    $gridTable->setTemplatePath(__DIR__ . '/templates/gridTable.latte');
    $gridTable->setSource($this->wrapperSection->getSource());
//    $gridTable->setSource(new ArrayDataSource($this->configureSection->getListSection()));
    $pk = $this->wrapperSection->getDatabasePk();
    $gridTable->setPrimaryKey($pk);
    $gridTable->setDefaultOrder($this->wrapperSection->getDatabaseOrderDefault());

    $elements = $this->wrapperSection->getElements();

    $gridTable->addColumn($pk, '#');

    $items = $this->wrapperSection->getItems();
    foreach ($items as $idItem => $configure) {
        $elem = $elements[$idItem]; // load element
        $column = $gridTable->addColumn($idItem, $elem->getTranslateNameContent());
        $column->setOrdering($configure['ordering']);
        $column->setData($configure);

//        $column->setCallback(function ($data, Column $context) { return $data; });
        $column->setCallback(function ($data, $context) use ($elem) { return $elem->getRenderRow($data); });
        if ($configure['type'] == 'checkbox') {
            $column->setTemplatePath(__DIR__ . '/templates/gridTableCheckbox.latte');
        }
    }

    // edit
    $gridTable->addButton('content-grid-table-edit')
        ->setLink($this->presenterName . ':edit', ['idSection' => $this->idSection, 'id' => '%id', null])
        ->setClass('edit-class')
        ->setData(['svg' => self::SVG_USE_EDIT])
        ->setPermission($this->idSection, WrapperSection::ACTION_EDIT);
//        ->setData($configure);

    // delete
    $gridTable->addButton('content-grid-table-delete')
        ->setLink($this->presenterName . ':delete', ['idSection' => $this->idSection, 'id' => '%id'])
        ->setClass('btn btn-delete')
        ->setData(['svg' => self::SVG_USE_DELETE])
        ->setPermission($this->idSection, WrapperSection::ACTION_DELETE)
        ->setConfirm('content-grid-table-delete-confirm')
        ->setCallback(function (array $data, Button $context) { return $data; });

    return $gridTable;
}
```

##### Drivers:
- ArrayDataSource(array $data)
- FinderDataSource(Finder $finder)

##### class GridTable
```php
cleanCache($name = 'grid')
setTemplatePath(string $path)
setSource(IDataSource $source): self
setItemPerPage(int $itemPerPage, bool $exception = false)
setPage(int $page, bool $exception = false)
setPaginator(Paginator $paginator, IComponent $visualPaginator = null)
setSortable(bool $state): self
setPrimaryKey(string $pk): self
setDefaultOrder(array $order): self
addButton(string $caption): Button
addColumn(string $name, string $header = null): Column
```

##### class Column
```php
setOrdering(bool $ordering = true): self
setData(array $data): self
setCallback(callable $callback): self  -  function ($data, Column $context) { return $data[$context->getName()]; }
setTemplatePath(string $path, array $data = []): self
```

##### class Button
```php
setCaption(string $caption): self
setLink(string $link, array $arguments = []): self
setUrl(array $arguments = []): self
setConfirm(string $text): self
setPermission(string $resource, string $privilege): self
setAllowed(bool $allowed): self
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

            $source = clone $this->wrapperSection->getSource();
            // ...

            return $gridTable;
        });
    }
```
