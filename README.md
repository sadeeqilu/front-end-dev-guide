# PORTAL FRONTEND DEV GUIDE

This document will explain the entire structure for nannodit portals. This document will also give some brief explanation and an insight for common files and structure for the portal projects. 
In this repository, you will find somewhat like a template or bootstrap for a portal application development. 

## Overview

Front-end web development is the development of the graphical user interface of a website, through the use of HTML, CSS, and JavaScript, so that users can view and interact with that website. 

The nannodit portal is a portal that is used to help a particular service users to easily sign up for a campaign/serivice. This portal has a frontend and a backend functionality, the frontend is for displaying the information and the backend contains all the logic and communicates with the main service through RESTful api. 

You can see examples of the portal at `http://5505.uz` and `http://8080.uz`.

## What is expected

On the user interface, the main things that are expected are as follows:

* Header section: awardees, news, frequently asked questions, news, language selector(uz/ru/en) and a participate button
* Participate modal: This should contain a text field for entering msisdn/pin and a send msisdn/pin button, using javascript this button and field will be replaced accordingly. There should be an alert section for error/success message above the text field. This modal should be closed with only a close button.
* Header enrichment: This is very important because the service operators provides the user's msisdn in the headers, we can use this to pre fill the msisdn field. 
* Banner section: A section on the homepage where the campaign is advertised and some information about the campaign with images and codes to send using the mobile phone.
* Alternate subscription: A section where you get the codes to enter directly on your mobile phone to subscribe.
* Footer section: Where the contact information is written.
* Languages: The portal always has more than one language and we use English language for development, after development we change the default language to Uzbek. The language texts are being stored in a file called `languages.yaml`, and with the help of the `mustache engine` we can render pages based on selected language through `index.php`.

## Folders

The default folders in the root directory of the project will be:

* `config` : This folder will be containing all the configuration settings for the app to run normally. 
* `doc` : Contains the documents related to the service, these include `process.puml`, `install.md` and `servers.yaml`.
* `public` :  All UI supporting files goes to this folder, these include `css`, `images` and `javascript` files.
* `views` : This folder contains the `mustache` view files.

## files

The default root directory files are:

* `index.php` : This file will contain the logic for the service using php. Mustache pages (the html) are being called to render by the index file depending on the language selected, language selections will be through this file as well.
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

`process.puml` : The sequence diagram for the service process, find this document template in this repository. Most of the campaign cases are similar, so you can find a template within this repository for this.

`install.md` : This file contains the steps involved in setting up and running the service on the production box. You can find a template in the respective folder within this directory.

`servers.yaml` : Contains all the installations made on the production box, this is mostly filled by the sysop guy.

### Public folder 

All css, javascript and image folders of the service goes into this folder. 

### Views

All the `mustache` files that contain the `html` for rendering to browser. This folder should contain at least one mustache file for the landing page and may contain other pages for news, awardees, contact, and FAQ.

## Conclusion
