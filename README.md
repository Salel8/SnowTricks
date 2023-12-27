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

Appuyez sur le bouton "Code" en vert, situé en haut de cette page. Choisissez entre HTTPS et SSH et copiez le nom du clone qui s'affiche. Créez un dossier où vous placerez le code du projet et ouvrez une fenêtre du terminal. Placez-vous dans ce dossier créé et clonez alors ce repository avec la commande git clone.

```bash
git clone git@github.com:Salel8/Snowtricks.git
```

Vous avez maintenant tout le projet en local mais avant de pouvoir l'utiliser, il vous faut créer votre base de données. Vous pouvez utiliser PHPMyAdmin pour créer votre base de données ou bien utiliser le jeu de données fourni dans le dossier data. Pour importer le jeu de données, rendez-vous dans la section "Importer" de PHPMyAdmin, sélectionnez le fichier "snowtricks.sql" et appuyer sur "Exécuter".

Une fois la base de données créée, il ne vous manque plus qu'à connecter ce projet à votre base de données. Pour cela, il vous faut créez un fichier .env.local et dans ce fichier il vous faudra insérer ce qui suit :

```php
DRIVER="driver"
DBNAME="dbname"
PORT=0000
USER="user"
PASSWORD="password"
HOST="host"
```

Veillez à bien modifier les champs "driver", "dbname", le port, "user", "password" et "host" avec ceux correspondant à votre base de données. Si vous avez importé la base de données, son nom devrait être "snowtricks". En local, souvent, le port est 8889.

Cette configuration étant établie, vous pouvez dorénavant profiter pleinement de l'ensemble du projet.

Use the package manager [pip](https://pip.pypa.io/en/stable/) to install foobar.

```bash
pip install foobar
```

## Démarrage

Pour lancer le projet, il faut commencer par installer toutes les dépendances du projet. Pour cela, lancez le serveur PHP puis, via le terminal, placez-vous dans le dossier créé plus tôt contenant le code du projet. Puis lancez la commande :

```bash
npm install
```
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