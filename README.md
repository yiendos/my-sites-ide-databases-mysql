# MySQL database server

[MySQL](https://www.mysql.com/) 8.4 for [my-sites-ide](https://github.com/yiendos/my-sites-ide): the database
your sites connect to as `mysql:3306`, with its data kept on the host in `storage/plugins/mysql/`.

Written for: developers running sites in my-sites-ide, including ones moving over from the MySQL
that used to ship inside the IDE.

## Contents

- [Installation](#installation)
- [Upgrading from the built-in MySQL](#upgrading-from-the-built-in-mysql)
- [Connecting to it](#connecting-to-it)
- [Command reference](#command-reference)
- [Configuration](#configuration)
- [What it uses from the IDE](#what-it-uses-from-the-ide)
- [Troubleshooting](#troubleshooting)

## Installation

A [my-sites-ide](https://github.com/yiendos/my-sites-ide) plugin. Add it to the `require` section of the IDE's `composer.local.json`
(the IDE's `composer.local-example.json` already lists it):

```json
"yiendos/my-sites-ide-databases-mysql": "@dev"
```

Then, from the IDE root:

```
composer update
php my-sites-ide ide:build      # builds the ${NAMESPACE}_mysql image, then sparks the IDE
```

Composer's `post-autoload-dump` hook registers the `databases:mysql-*` commands and the `mysql`
compose service, and creates `storage/plugins/mysql/`. The service has `autostart: true`, so
`ide:spark` starts it alongside `APP` - you don't need to list `mysql` in `APP`.

## Upgrading from the built-in MySQL

MySQL used to live in the IDE at `_dev/environment/databases/mysql/`. The image, service name
(`mysql`), credentials and port are unchanged, so sites using `DB_HOST=mysql` keep working. The parts that moved:

| Before | Now |
|---|---|
| `_dev/environment/databases/mysql/` | this package |
| data in `_dev/environment/databases/mysql/data/` | data in `storage/plugins/mysql/data/` |
| container called `db` | container called `mysql`, still reachable as `db` on the IDE network |
| `mysql` listed in `APP` | started by `autostart` |
| port, database and user fixed in the compose file | `MYSQL_PORT`, `MYSQL_DATABASE`, `MYSQL_USER`, `MYSQL_PASSWORD`, `SQL_MODE` - same defaults |

Your databases don't move by themselves. Stop the old container, copy the data, then start the plugin's:

```
docker stop db
cp -a _dev/environment/databases/mysql/data storage/plugins/mysql/data
php my-sites-ide databases:mysql-start
```

Check your databases are there (`databases:mysql-cli`, then `show databases;`) before deleting
the old `_dev/environment/databases/mysql/` folder - git no longer tracks it, so it's left on disk.

## Connecting to it

Inside the IDE, a site reaches MySQL by its service name. For Laravel, in the site's `.env`:

```
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=default
DB_USERNAME=mysites
DB_PASSWORD=mysites
```

`databases:mysql-start` prints the host lines. The `db` alias is kept for anything written when
the container was called `db`. Don't rely on it if the [MariaDB plugin](https://github.com/yiendos/my-sites-ide-databases-mariadb)
is running too, since both answer to `db`.

From the host (a GUI client, a script), use `localhost:3306` - or `MYSQL_PORT`. Root has no password.

## Command reference

| Command | What it does |
|---|---|
| `databases:mysql-start` | `docker compose up -d mysql`. Also recreates a running container whose compose config has changed (e.g. a new port). The data survives |
| `databases:mysql-stop` | `docker compose stop mysql`, leaving the rest of the IDE running. The next `ide:spark` starts it again |
| `databases:mysql-cli [database]` | A `mysql` client as root, inside the container |

## Configuration

| Variable | Default | What it does |
|---|---|---|
| `MYSQL_PORT` | `3306` | The host port. Containers on the IDE network always use `mysql:3306` |
| `MYSQL_DATABASE` | `default` | The database created on the first start |
| `MYSQL_USER` / `MYSQL_PASSWORD` | `mysites` / `mysites` | The user created on the first start |
| `SQL_MODE` | `ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION` | The server's `sql_mode` |

Defaults live in this plugin's `.env`. Set any of them in the IDE's root `.env`, which wins, then
run `databases:mysql-start`. `php my-sites-ide ide:plugin-env yiendos/my-sites-ide-databases-mysql`
copies them in, commented out.

`MYSQL_DATABASE`, `MYSQL_USER` and `MYSQL_PASSWORD` only apply when `storage/plugins/mysql/data/` is empty.
After that the data folder keeps its own.

Server settings live in `conf/my.cnf`, mounted at `/etc/my.cnf`.

## What it uses from the IDE

| From the IDE | Used for |
|---|---|
| `NAMESPACE` (root `.env`) | the image name, `${NAMESPACE}_mysql` |
| the `my-sites-ide` network | sites reaching it as `mysql` (and `db`) |
| `IDE_ROOT` | the data folder, `storage/plugins/mysql/data/` |
| `"storage": true` | the IDE creating `storage/plugins/mysql/` (also mounted at `/storage`) |

## Troubleshooting

**`SQLSTATE[HY000] [2002]` / `getaddrinfo for mysql failed`.** MySQL isn't running:
`php my-sites-ide databases:mysql-start`. Inside a container, `localhost` or `127.0.0.1` is that
container itself - use `mysql`.

**Port 3306 is already allocated.** Another container (the MariaDB plugin, an old `db` container)
or a MySQL on your Mac has it. `docker ps --filter publish=3306` shows which, or move this one with `MYSQL_PORT`.

**My databases are missing.** The plugin reads `storage/plugins/mysql/data/` - see
[Upgrading from the built-in MySQL](#upgrading-from-the-built-in-mysql).
