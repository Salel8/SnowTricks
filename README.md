# Snowtricks

Ce projet, réalisé avec PHP/Symfony, est un site communautaire sur des figures de snowboard. Le projet a pour but de réaliser des articles, de les lire, de les modifier et de les supprimer. Le site dispose d'un système de compte utilisateur avec l'enregistrement/connexion d'un utilisateur ainsi qu'un système d'oublie du mot de passe et une validation du compte utilisateur par email. Une base de données est nécessaire pour stocker les différentes informations sur les articles ainsi que sur les utilisateurs.

## Pré-requis

```php
PHP
Symfony
Git
Composer
Base de données MySQL
```

## Installation

Appuyez sur le bouton "Code" en vert, situé en haut de cette page. Choisissez entre HTTPS et SSH et copiez le nom du clone qui s'affiche. Créez un dossier où vous placerez le code du projet et ouvrez une fenêtre du terminal. Placez-vous dans ce dossier créé et clonez alors ce repository avec, par exemple en SSH, la commande git clone :

```bash
git clone git@github.com:Salel8/SnowTricks.git
```

Vous avez maintenant tout le projet en local mais avant de pouvoir l'utiliser, il vous faut créer votre base de données. Vous pouvez utiliser PHPMyAdmin pour créer votre base de données ou bien utiliser le jeu de données fourni dans le dossier data.
 
Pour importer le jeu de données, rendez-vous dans la section "Importer" de PHPMyAdmin, sélectionnez le fichier "snowtricks.sql" et appuyer sur "Exécuter".

Une fois la base de données créée, il ne vous manque plus qu'à connecter ce projet à votre base de données. Pour cela, il vous faut créez un fichier .env.local et dans ce fichier il vous faudra insérer ce qui suit :

```php
DRIVER="driver"
DBNAME="dbname"
PORT=0000
USER="user"
PASSWORD="password"
HOST="host"
```

Veillez à bien modifier les champs "driver", "dbname", le port, "user", "password" et "host" avec ceux correspondant à votre base de données. Si vous avez importé la base de données, son nom devrait être "snowtricks". En local, si vous prenez la base de données fourni avec le projet, le driver est pdo_mysql, le dbname est snowtricks, le port est 8889, le host est 127.0.0.1, quand au user et au password il s'agit de ceux utilisé dans votre configuration PHPMyAdmin. Soit :

```php
DRIVER="pdo_mysql"
DBNAME="snowtricks"
PORT="8889"
USER="user"
PASSWORD="password"
HOST="127.0.0.1"
```

Vous pouvez aussi créer votre propre base de données. Pour cela, après avoir rempli le fichier de configuration .env.local avec les données ci-dessus, il vous faut taper cette commande dans le terminal :

```bash
php bin/console doctrine:database:create
```

Votre base de données étant créée et configurée, il vous faut régler le mailer dsn dans le fichier .env.local, ce qui vous permettra d'envoyer des emails.

Pour cela, il vous faut utiliser la commande terminale, en remplaçant le sendgrid par le service utilisé (toujours à la racine de votre projet) :

```bash
composer require symfony/sendgrid-mailer
```

Puis, il faut configurer le mailer_dsn dans le fichier .env.local en ajoutant :

```php
MAILER_DSN=sendgrid://KEY@default
```

Vous pouvez vous aider de la [documentation officielle de symfony](https://symfony.com/doc/current/mailer.html) pour cette étape. Par exemple, si vous utilisez un compte mailjet (vous pouvez vous en créer un gratuitement), vous devrez utiliser la commande terminale :

```bash
composer require symfony/mailjet-mailer
```

Puis dans le fichier .env.local, vous devrez ajouter le mailer dsn. Vous pouvez utiliser l'accès par SMTP ou encore l'accès par API, vous ne devez en utiliser qu'un seul entre les deux exemples suivants :

```php
MAILER_DSN=mailjet+smtp://ACCESS_KEY:SECRET_KEY@default
```

```php
MAILER_DSN=mailjet+api://ACCESS_KEY:SECRET_KEY@default
```

Il vous suffit de récupérer l'ACCESS_KEY et la SECRET_KEY dans votre compte mailjet et de les insérer dans la commande ci-dessus. 

Vous pouvez aussi utiliser un autre service pour envoyer vos emails, les démarches seront identiques (installation du service dans la commande terminale, remplacement du mailer dsn dans le fichier .env.local en indiquant l'access key et la secret key obtenues auprès du service).

Cette configuration étant établie, vous pouvez dorénavant profiter pleinement de l'ensemble du projet.

## Démarrage

Pour lancer le projet, il faut commencer par installer toutes les dépendances du projet. Pour cela, lancez le serveur PHP puis, via le terminal, placez-vous dans le dossier créé plus tôt contenant le code du projet. Puis lancez la commande :

```bash
composer install
```
Cette commande permet d'installer toutes les dépendances liées à composer.

Une fois cette commande réalisée, vous devez lancer le serveur de symfony avec la commande :

```bash
symfony server:start
```

Puis, ouvrez votre navigateur et allez sur la page  [http://localhost:8000/posts](http://localhost:8000/posts) pour vous rendre sur la page d'accueil du site web.


## Fabriqué avec 

HTML - CSS - Twig

PHP - Symfony

PHPMyAdmin - MySQL

Git - Composer

VSCode

## Versions

PHP 8.2.10

Symfony 6.3

## Auteur

Samir Mehal