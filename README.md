# PORTAL FRONTEND DEV GUIDE

This document will explain the entire structure for portal applications. This document will also give some brief explanation and an insight for common files and structure. 
In this repository, you will find the template for a portal frontend to run successfully. 

## Folders

The default folders in the root directory of the project will be:

* `config` : This folder will be containing all the configuration settings for the app to run normally. 
* `doc` : Contains the documents related to the service, these include `process.puml`, `install.md` and `servers.yaml`.
* `public` :  All UI supporting files goes to this folder, these include `css`, `images` and `javascript` files.
* `views` : This folder contains the `mustache` view files.

## files

The default root directory files are:

* `index.php` : This file will contain the logic for the service using php. Mustache pages are being called to render through this file, language selections will be through this file as well.
* `.gitignore` : The files/folders to be ignored will be listed here, vendor and log folders, and composer.lock file.
* `.htaccess` : All the restrictions and checks for apache should be in this file, ensure that all `.php` files are changed to the filename withouth the `.php`, all non-existing pages are rerouted to the home page.
* `composer.json` : Ensure that `abcvyz/yamlx`, `abcvyz/config`, `abcvyz/logger`, `guzzlehttp/guzzle`, `kint-php/kint` and `mustache/mustache` dependencies should be in this file to be installed using composer.
* `install.sh` : After all has been completed and put in production box, this file comes in handy, run the bash script in the production box with the settings updated to be perculiar to the service.
* `{{funnyname.php}}` (for returning headers)
* `README.md` : Explain the service in this document.

### Config folder files

* `{languages|communique}.yaml` : This file should contain all the language text translation mappings accordingly. For example: 

```yaml
# this is an example for uzbek and russian language settings

uz: &default
  language: uz
  head:
    meta:
      description: "Участвуйте в викторине &quot;Словомания&quot; от компании Uzmobile. Выигрыш призов в Узбекистане."
      keywords : "розыгрыш, призы, uzmobile"
    title: Розыгрыш призов от компании Uzmobile


ru: 
  language: ru

  head:
    meta:
      description: "Участвуйте в викторине &quot;Словомания&quot; от компании Uzmobile. Выигрыш призов в Узбекистане."
      keywords : "розыгрыш, призы, uzmobile"
    title: Розыгрыш призов от компании Uzmobile
```

* `{application_name}.conf` : The apache virtualhost configuration settings will be written in this file, for instance:

```conf
<VirtualHost *:80>
    ServerName 206.189.122.11
    DocumentRoot "/nannodit/portal-wordmaster-uzmobile-uz/"
    ErrorLog /nannodit/portal-wordmaster-uzmobile-uz/log/apache_error.log
    CustomLog /nannodit/portal-wordmaster-uzmobile-uz/log/apache_access.log combined

    <Directory /nannodit/portal-wordmaster-uzmobile-uz>
        Options Indexes FollowSymLinks
        DirectoryIndex index.php
        AllowOverride All
        Require all granted
    </Directory>

</VirtualHost>
```

In the above example, you will see that `apache server` will host this application in the default port 80, the main thing I want to point out is that we need to set the `ErrorLog` and `CustomLog` to be saving in the project folder directly for easy access.

`log_v21_config.yaml` : The log setup settings will be stored in this file.

### Doc folder files

`process.puml` : The sequence diagram for the service process, find this document template in this repository.

`install.md` : This file contains the steps involved in setting up and running the service on the production box.

`servers.yaml` : Contains all the installations made on the production box.

### Public folder 

All css, javascript and image files of the service goes into this folder.

### Views

All the `mustache` files that have the `html` for rendering to browser. This folder should contain at least one mustache file for the landing page and may contain other pages for news, awardees, contact, and FAQ.