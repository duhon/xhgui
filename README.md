Overview
========

This fork add ability to use xhgui as docker container
Just use ```docker pull duhon/xhgui``` and ```docker pull mongo``` for link mongo to xhgui
Or use docker-compose approach:

````xml
version: '3.2'
services:
  app:
    image: _YOUR_APP_
  mongodb:
    image: mongo
    ports:
      - "27017:27017"
  xhgui:
    image: duhon/xhgui
    depends_on:
      - mongodb
    ports:
      - "0.0.0.0:8088:80"
````

For pass data from tideways to xhgui just add **tideways.ini** to your Application


Below you can find default docs:

How To Run
==========

1. Make sure you have installed tideways php extension. If you haven't - go to [tideways extension](https://github.com/tideways/php-profiler-extension) and install it.
2. Go to php.ini file and add above configuration for tideways;
3. Restart php-fpm and apache;
4. Make sure you have installed docker. If not - go to [Docker](https://docs.docker.com/install/) and install it;
5. Make sure you have installed docker-compose. If not - go to [Docker Compose](https://docs.docker.com/compose/install/) and install it;
6. Save docker-compose file from above;
7. Run `docker-compose up -d` to start containers;
8. Your xhgui web interface must be available on `0.0.0.0:8088`
9. Use [auto_prepand_file](http://php.net/manual/en/ini.core.php#ini.auto-prepend-file) to add file `external/header.php` to your application;
10. Run your application and check results on xhgui web interface.

xhgui
=====

A graphical interface for XHProf data built on MongoDB.

This tool requires that [Tideways](https://github.com/tideways/php-profiler-extension) are installed.
Tideways is a PHP Extension that records and provides profiling data.
XHGui (this tool) takes that information, saves it in MongoDB, and provides
a convenient GUI for working with it.

[![Build Status](https://travis-ci.org/perftools/xhgui.svg?branch=master)](https://travis-ci.org/perftools/xhgui)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/perftools/xhgui/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/perftools/xhgui/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/perftools/xhgui/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/perftools/xhgui/?branch=master)


Configuration
=============

Configure XHGui Profiling Rate
-------------------------------

After installing XHGui, you may want to change how frequently you
profile the host application. The `profiler.enable` configuration option
allows you to provide a callback function that specifies the requests that
are profiled. By default, XHGui profiles 1 in 100 requests.

The following example configures XHGui to only profile requests
from a specific URL path:

The following example configures XHGui to profile 1 in 100 requests,
excluding requests with the `/blog` URL path:

```php
// In config/config.php
return array(
    // Other config
    'profiler.enable' => function() {
        $url = $_SERVER['REQUEST_URI'];
        if (strpos($url, '/blog') === 0) {
            return false;
        }
        return rand(1, 100) === 42;
    }
);
```

In contrast, the following example configured XHGui to profile *every*
request:

```php
// In config/config.php
return array(
    // Other config
    'profiler.enable' => function() {
        return true;
    }
);
```

Configure 'Simple' URLs Creation
--------------------------------

XHGui generates 'simple' URLs for each profile collected. These URLs are
used to generate the aggregate data used on the URL view. Since
different applications have different requirements for how URLs map to
logical blocks of code, the `profile.simple_url` configuration option
allows you to provide specify the logic used to generate the simple URL.
By default, all numeric values in the query string are removed.

```php
// In config/config.php
return array(
    // Other config
    'profile.simple_url' => function($url) {
        // Your code goes here.
    }
);
```

The URL argument is the `REQUEST_URI` or `argv` value.

Configure ignored functions
---------------------------

You can use the `profiler.options` configuration value to set additional options
for the profiler extension. This is useful when you want to exclude specific
functions from your profiler data:

```php
// In config/config.php
return array(
    //Other config
    'profiler.options' => [
        'ignored_functions' => ['call_user_func', 'call_user_func_array']
    ]
);
```

In addition, if you do not want to profile all PHP built-in functions,
you can make use of the `profiler.skip_built_in` option.

Profiling a Web Request or CLI script
=====================================

Using [xhgui-collector](https://github.com/perftools/xhgui-collector) you can
collect data from your web applications and CLI scripts. This data is then
pushed into xhgui's database where it can be viewed with this application.

Saving & Importing Profiles
---------------------------

If your site cannot directly connect to your MongoDB instance, you can choose
to save your data to a temporary file for a later import to XHGui's MongoDB
database.

To configure XHGui to save your data to a temporary file,
change the `save.handler` setting to `file` and define your file's
path with `save.handler.filename`.

To import a saved file to MongoDB use XHGui's provided
`external/import.php` script.

Be aware of file locking: depending on your workload, you may need to
change the `save.handler.filename` file path to avoid file locking
during the import.

The following demonstrate the use of `external/import.php`:

```bash
php external/import.php -f /path/to/file
```

**Warning**: Importing the same file twice will load twice the run datas inside
MongoDB, resulting in duplicate profiles


Limiting MongoDB Disk Usage
---------------------------

Disk usage can grow quickly, especially when profiling applications with large
code bases or that use larger frameworks.

To keep the growth
in check, configure MongoDB to automatically delete profiling documents once they
have reached a certain age by creating a [TTL index](http://docs.mongodb.org/manual/core/index-ttl/).

Decide on a maximum profile document age in seconds: you
may wish to choose a lower value in development (where you profile everything),
than production (where you profile only a selection of documents). The
following command instructs Mongo to delete documents over 5 days (432000
seconds) old.

```
$ mongo
> use xhprof
> db.results.ensureIndex( { "meta.request_ts" : 1 }, { expireAfterSeconds : 432000 } )
```

Add indexes to MongoDB to improve performance.

XHGui stores profiling information in a `results` collection in the
`xhprof` database in MongoDB. Adding indexes improves performance,
letting you navigate pages more quickly.

To add an index, open a `mongo` shell from your command prompt.
Then, use MongoDB's `db.collection.ensureIndex()` method to add
the indexes, as in the following:

```
$ mongo
> use xhprof
> db.results.ensureIndex( { 'meta.SERVER.REQUEST_TIME' : -1 } )
> db.results.ensureIndex( { 'profile.main().wt' : -1 } )
> db.results.ensureIndex( { 'profile.main().mu' : -1 } )
> db.results.ensureIndex( { 'profile.main().cpu' : -1 } )
> db.results.ensureIndex( { 'meta.url' : 1 } )
> db.results.ensureIndex( { 'meta.simple_url' : 1 } )
```


Waterfall Display
-----------------

The goal of XHGui's waterfall display is to recognize that concurrent requests can
affect each other. Concurrent database requests, CPU-intensive
activities and even locks on session files can become relevant. With an
Ajax-heavy application, understanding the page build is far more complex than
a single load: hopefully the waterfall can help. Remember, if you're only
profiling a sample of requests, the waterfall fills you with impolite lies.

Some Notes:

 * There should probably be more indexes on MongoDB for this to be performant.
 * The waterfall display introduces storage of a new `request_ts_micro` value, as second level
   granularity doesn't work well with waterfalls.
 * The waterfall display is still very much in alpha.
 * Feedback and pull requests are welcome :)

Using Tideways Extension
========================

The XHProf PHP extension is not compatible with PHP7.0+. Instead you'll need to
use the [tideways extension](https://github.com/tideways/php-profiler-extension).

Once installed, you can use the following configuration data:

```ini
[tideways]
extension="/path/to/tideways/tideways.so"
tideways.connection=unix:///usr/local/var/run/tidewaysd.sock
tideways.load_library=0
tideways.auto_prepend_library=0
tideways.auto_start=0
tideways.sample_rate=100
```

TODO:
=====
add graphviz

License
=======

Copyright (c) 2013 Mark Story & Paul Reinheimer

Permission is hereby granted, free of charge, to any person obtaining a
copy of this software and associated documentation files (the
"Software"), to deal in the Software without restriction, including
without limitation the rights to use, copy, modify, merge, publish,
distribute, sublicense, and/or sell copies of the Software, and to
permit persons to whom the Software is furnished to do so, subject to
the following conditions:

The above copyright notice and this permission notice shall be included
in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY
CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT,
TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE
SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
