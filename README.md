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
    - FlashMessage
```

usage:
```php
protected function createComponentFlashMessage(FlashMessage $flashMessage)
{
    // $flashMessage->setTemplatePath(__DIR__ . '/templates/FlashMessage.latte');
    return $flashMessage;
}
```

usage:
```latte
{control flashMessage}
```
