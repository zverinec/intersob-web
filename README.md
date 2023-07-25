InterSob website
----------------

Author: Jan Drábek
Maintenance: Jan Drábek <jan@drabek.cz>

Installation
------------

1. Clone this repository
2. Install dependencies through Composer
3. Make /temp and /log writable
4. Create /app/config/config.local.neon (empty or overwrite values)
5. Run it

Local deployment
----------------

For local deployment CLI tool `loopbind` installed via `composer global require kiwicom/loopbind`.

Run `loopbind apply` and then `docker copmose up -d` in the root of the project.

To access website use `https://intersob.test`.
To access Adminer for DB access use `https://intersob.test:8080` with `user` and `password` as credentials.

Via adminer import SQL schema from `resources/database/schema.sql`, first user will have credentials `admin` and `admin`.
