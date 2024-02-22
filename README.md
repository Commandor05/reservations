## Reservations

This is simple Laravel project with Docker deploy

### Short functionality description

Adnin:

- Manage companies and their users
- View / Manage activities

Company owner:

- Manage guides for their companies
- Manage activities and assign guides

Public:

- View all activities
- Register for the activity

Customer:

- View my activities
- Cancel my reservations

Guide:

- View activities Guide assigned to
- Export PDF of the activity participants

## The project up and running

Up docker containers for the first time with the build:

```
docker-compose up --build
```

Apply migrations:

```
docker-compose run --rm  artisan migrate
```

Running Seeders:

```
docker-compose run --rm  artisan db:seed
```

Install PHP packages:

```
docker-compose run --rm  composer install
```

Install NPM packages:

```
docker-compose run --rm  npm install
```

Build NPM packages:

```
docker-compose run --rm  npm run build
```

That's all abut the project setup it shoul work now.

For future run containers without build:

```
docker-compose up
```

or in detached mode

```
docker-compose -d up
```

## Running tests

For running all tests:

```
docker-compose run --rm  artisan test
```

For running the particular test

```
docker-compose run --rm  artisan test --filter=CompanyTest
```

## Build Prooduction

```
docker-compose -f docker-compose.yml -f docker-compose.prod.yml up --build nginx
```
