# MemeMe

Discord bot that generates memes, built using [p7g/discord.php](https://github.com/p7g/discord.php) and [memegen](https://github.com/jacebrowning/memegen).

## Prerequisites

To run this, you will need:
- PHP 7.1 or higher
- composer
- the sqlite driver for PHP
- mbstring PHP extension

To avoid insane CPU usage, install the `event` extension for PHP as well.

## Starting it

First, clone the repository and enter the directory.
```bash
git clone https://github.com/p7g/MemeMe
cd MemeMe
```

Migrate the database:
```bash
./vendor/bin/phinx migrate -e production
```

Make a `.env` file and populate it with your bot token and sentry DSN:
```bash
echo "TOKEN=<your token>" > .env
echo "SENTRY_DSN=<your DSN>" >> .env
```

Then run `index.php`:
```bash
php index.php
```
