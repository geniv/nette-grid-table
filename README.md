Grid table
==========

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
"geniv/nette-autowired": ">=1.0.0",
"geniv/nette-general-form": ">=1.0.0",
"geniv/nette-visual-paginator": ">=1.0.0"
```

Include in application
----------------------
neon configure:
```neon
...
    events:
        - AjaxFlashMessageEvent
#        - AjaxFlashMessageEvent(otherNameComponent)
#        - AjaxFlashMessageEvent(otherNameComponent, otherFallBack)
```

neon configure services:
```neon
services:
- GridTable\GridTable
- VisualPaginator  
```

usage:
```php
protected function createComponentGridTable(GridTable $gridTable): GridTable
{
    $gridTable->setSource($this->wrapperSection->getSource());

    $gridTable->setPrimaryKey($this->wrapperSection->getDatabasePk());
    $gridTable->setItemPerPage($this->wrapperSection->getDatabaseLimit());
    $gridTable->setEmptyText('content-grid-table-empty');
    $gridTable->setDefaultOrder($this->wrapperSection->getDatabaseOrderDefault());

    $elements = $this->wrapperSection->getElements();

    $items = $this->wrapperSection->getItems();
    foreach ($items as $idItem => $item) {
        $elem = $elements[$idItem]; // load element
        $column = $gridTable->addColumn($idItem, $elem->getTranslateNameContent());
        $column->setOrdering($item['ordering']);
        $column->setCallback(function ($data) use ($elem) {
            return $elem->getRenderRow($data);
        });
    }

    // edit
    $gridTable->addButton('content-grid-table-edit')
        ->setLink($this->presenterName . ':edit', [$this->idSection, 'id'])
        ->setPermission($this->idSection, WrapperSection::ACTION_EDIT);

    // delete
    $gridTable->addButton('content-grid-table-delete')
        ->setLink($this->presenterName . ':delete', [$this->idSection, 'id'])
        ->setPermission($this->idSection, WrapperSection::ACTION_DELETE)
        ->setConfirm('content-grid-table-delete-confirm');

    return $gridTable;
}
```

usage:
```latte
<a n:if="$user->isAllowed($idSection, AdminElement\WrapperSection::ACTION_ADD)" n:href="add $idSection">{_'content-grid-table-add'}</a>
<br>
{control gridTable}
```
