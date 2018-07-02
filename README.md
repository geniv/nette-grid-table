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

    $gridTable->setTemplatePath(__DIR__ . '/templates/gridTable.latte');
    $gridTable->setSource($this->wrapperSection->getSource());
    $gridTable->setPrimaryKey($this->wrapperSection->getDatabasePk());
    $gridTable->setDefaultOrder($this->wrapperSection->getDatabaseOrderDefault());

    $elements = $this->wrapperSection->getElements();

    $items = $this->wrapperSection->getItems();
    foreach ($items as $idItem => $configure) {
        $elem = $elements[$idItem]; // load element
        $column = $gridTable->addColumn($idItem, $elem->getTranslateNameContent());
        $column->setOrdering($configure['ordering']);
        $column->setData($configure);

        $column->setCallback(function ($data) use ($elem) { return $elem->getRenderRow($data); });
        if ($configure['type'] == 'checkbox') {
            $column->setTemplatePath(__DIR__ . '/templates/gridTableCheckbox.latte');
        }
    }

    // edit
    $gridTable->addButton('content-grid-table-edit')
        ->setLink($this->presenterName . ':edit', ['idSection' => $this->idSection, 'id' => '%id', null])
        ->setClass('edit-class')
        ->setPermission($this->idSection, WrapperSection::ACTION_EDIT);

    // delete
    $gridTable->addButton('content-grid-table-delete')
        ->setLink($this->presenterName . ':delete', ['idSection' => $this->idSection, 'id' => '%id'])
        ->setPermission($this->idSection, WrapperSection::ACTION_DELETE)
        ->setConfirm('content-grid-table-delete-confirm');

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
