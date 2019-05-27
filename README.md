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

1. Clone this repo to your oms folder on your host machine;
2. Make sure you have installed tideways php extension on your vagrant box. If you haven't - go to [tideways extension](https://github.com/tideways/php-profiler-extension) and install it;
    * Note, for our current vagrant box (OMS721) you might need to install `autoconf` to be able to install tideways extension. Check "Install autoconf for OMS721" below to do that.
    * Note, for our current vagrant box (OMS721) this step is automated with puppet. Check if file `/home/vagrant/php-xhprof-extension/modules/tideways_xhprof.so` exists inside a box. If not - run `vagrant provision`.
3. Go to php.ini file and add below configuration for tideways;
    ```ini
       [tideways]
       extension="/path/to/tideways/tideways.so"
       tideways.connection=unix:///usr/local/var/run/tidewaysd.sock
       tideways.load_library=0
       tideways.auto_prepend_library=0
       tideways.auto_start=0
       tideways.sample_rate=100
    ```
4. Restart php-fpm and apache;
5. Make sure you have installed docker on your host machine. If not - go to [Docker](https://docs.docker.com/install/) and install it;
6. Make sure you have installed docker-compose on your host machine. If not - go to [Docker Compose](https://docs.docker.com/compose/install/) and install it;
7. Go to your xhgui folder on your host machine;
8. Run `composer install --ignore-platform-reqs`
8. Run `docker-compose up -d` to start containers;
9. Your xhgui web interface must be available on [http://0.0.0.0:8088](http://0.0.0.0:8088)
10. Your xhgui api interface must be visible from inside a vagrant box on `http://10.0.2.2:8088/api.php`;
    * If xhgui api url is different from `http://10.0.2.2:8088/api.php` - go to `external/header.php` file and update const `PATH_TO_XHGUI_API` with a proper value.
11. To enable profiling you need manually include `external/header.php` file to a php script that you want to profile;
    * Add `include "/platform/svc/app/oms/xhgui/external/header.php";` to `backoffice/web/app_dev.php` to profile backoffice;
    * Add `include "/platform/svc/app/oms/xhgui/external/header.php";` to `oms/web/app.php` to profile oms;
    * Add `include "/platform/svc/app/oms/xhgui/external/header.php";` to `oms/app/console` to profile console commands;
    * Add `include "/platform/svc/app/oms/xhgui/external/header.php";` to `oms/app/job-console.php` to profile cron jobs;
    * Note, you need to include above file right after the line `$loader = require __DIR__.'/../app/autoload.php';`; 
12. Run your application and check results on xhgui web interface.

xhgui
=====

A graphical interface for XHProf data built on MongoDB.

This tool requires that [Tideways](https://github.com/tideways/php-profiler-extension) are installed.
Tideways is a PHP Extension that records and provides profiling data.
XHGui (this tool) takes that information, saves it in MongoDB, and provides
a convenient GUI for working with it.

Using Tideways Extension
========================

The XHProf PHP extension is not compatible with PHP7.0+. Instead you'll need to
use the [tideways extension](https://github.com/tideways/php-profiler-extension).

Once installed, you can use the following configuration data:

```ini
[tideways]
extension=tideways_xhprof.so
tideways.connection=unix:///usr/local/var/run/tidewaysd.sock
tideways.load_library=0
tideways.auto_prepend_library=0
tideways.auto_start=0
tideways.sample_rate=100
```

Install autoconf for OMS721
=======
```bash
sudo yum install -y m4
sudo yum install -y perl-Data-Dumper.x86_64

wget http://ftp.gnu.org/gnu/autoconf/autoconf-2.69.tar.gz
gunzip autoconf-2.69.tar.gz
tar xvf autoconf-2.69.tar
cd autoconf-2.69
./configure
sudo make
sudo make install
```

Add indexes to MongoDB
=======
```bash
$ mongo
> use xhprof
> db.results.ensureIndex( { 'meta.SERVER.REQUEST_TIME' : -1 } )
> db.results.ensureIndex( { 'profile.main().wt' : -1 } )
> db.results.ensureIndex( { 'profile.main().mu' : -1 } )
> db.results.ensureIndex( { 'profile.main().cpu' : -1 } )
> db.results.ensureIndex( { 'meta.url' : 1 } )
> db.results.ensureIndex( { 'meta.simple_url' : 1 } )
```

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
