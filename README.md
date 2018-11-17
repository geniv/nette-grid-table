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
"geniv/nette-grid-table": ">=1.0.0"
```

require:
```json
"php": ">=7.0.0",
"nette/nette": ">=2.4.0",
"dibi/dibi": ">=3.0.0",
"geniv/nette-general-form": ">=1.0.0"
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
    $gridTable->setVisualPaginator($visualPaginator);
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
