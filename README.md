TemplateEngine2
===============
[![Build Status](https://travis-ci.org/cobexer/templateengine2.svg?branch=master)](https://travis-ci.org/cobexer/templateengine2)
[![Coverage Status](https://img.shields.io/coveralls/cobexer/templateengine2.svg)](https://coveralls.io/r/cobexer/templateengine2?branch=master)

The TemplateEngine2 is a PHP based Templating system that is highly customizable,
if it doesn't do what you need it to - then add it!

Have a look at the `plugins/` directory to see how that works ;)

Getting started
===============
* clone this repository somewhere
* call `sudo make install-phpunit` (or make sure phpunit is installed)
* call `make`
* watch the build process ;)
* take the `TemplateEngine2.php` and `TE_setup.php` files from the `build/` directory and include them in your project
* done start using the TemplateEngine2 =)

Usage
=====
```php

// assuming you put those 2 files into the include directory in your project:
require_once('include/TemplateEngine2.php');

// create your TemplateEngine2 instance
$te= new TemplateEngine();
$te->setRootPath('./'); // the root path is the root of your project browser relative tho the current script
$te->setTemplatePath('templates/standard'); // the directory where the template resides (relative to the ROOT_PATH ^)


// set any data that the template will be able to use by calling $te->set($name, $value)
// $name may only use uppercase letters 0-9 and the _ (that is with the included plugins...)
$tasks= array();
$tasks[]= array('NAME' => 'Task1', 'DESCRIPTION' => 'description1');
$tasks[]= array('NAME' => 'Task2', 'DESCRIPTION' => 'description2');
$te->set('MY_TASKS', $tasks); // $tasks may actually be anything (any type), the template decides how it will be presented


$te->output('yourTemplate.tpl'); // will process and output ./templates/standard/yourTemplate.tpl

// a tip:
// in case you wan't to see which variables are available to a template append the "te_dump" parameter to the URL
// like: http://localhost/project/index.php?te_dump or http://localhost/project/index.php?arg1=111&te_dump
// this will also show you the few built in variables ;)
```

The files for this example:


The example template `./templates/standard/yourTemplate.tpl`:

```html
{LOAD=header.tpl}
There are {MY_TASKS|LEN} Tasks.
<ul>
{FOREACH[MY_TASKS]=task.tpl}
</ul>
{LOAD=footer.tpl}
```

What happens in this template?

* `{LOAD=header.tpl}`: loads the file `header.tpl` from your templates directory into the output.
* `{MY_TASKS|LEN}`: this takes the `MY_TASKS` variable and processes it with the `LEN` escape method, the result is the number of entries in the array.
* `{FOREACH[MY_TASKS]=task.tpl}`: the `FOREACH` directive takes the `MY_TASKS`, validates it as an array and adds `task.tpl` once for every element.
* `{LOAD=footer.tpl}`: loads the file `footer.tpl` from your templates directory into the output.



The `./templates/standard/header.tpl`:

```html
<!doctype html>
<html>
  <head>
    <!-- ... -->
  </head>
<body>
```


The `./templates/standard/task.tpl`:

```html
  <li>{NAME}: {DESCRIPTION}</li>
```

The `FOREACH` directive makes all entries of the array element (and a few extra things) available as template variables, other variables can be accessed as well.


The `./templates/standard/footer.tpl`:

```html
</body>
</html>
```

----
For more read the included documentation (German) or read the code ;) that will not only show you what is available but also how exactly they work and it will show you how easy it is to extend the built in functionality.

Issues, Comments, Pull Requests all welcome^^
