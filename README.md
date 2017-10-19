Установка
---------

### debian

```bash
wget https://github.com/z7zmey/codegraph/releases/download/v0.1.1/codegraph_0.1.1_linux_amd64.deb
sudo dpkg -i codegraph_0.1.1_linux_amd64.deb

```

### mac os

```bash
wget https://github.com/z7zmey/codegraph/releases/download/v0.1.1/codegraph_0.1.1_darwin_amd64.tar.gz
mkdir codegraph
tar -zxvf codegraph_0.1.1_darwin_amd64.tar.gz -C codegraph
```

homebrew планируется

docker
------

```bash
docker run --rm -ti -v /path/to/php/src:/src -p 8080:8080 z7zmey/codegraph
```

Требования
----------

В системе должен быть установлен php 7.0 или выше. 


Использование
-------------

```bash
cd /path/to/php/src
codegraph
```

Dashboard: http://localhost:8080/app

Флаги
-----

-path -P путь к php исходникам * (default current path)

-exclude -e путь к исключаемой папке *

-debug -d отобразить дебаг инфо
  
-host -h dashboard хост (default "127.0.0.1")

-port -p dashboard порт (default 8080)

-php путь к испольняемому файлу php (default "php")

\* можно передать несколько раз для указания нескольких попок TODO: сейчас не совсем корректоно обрабатываются относительные пути
