# FRONTEND DEV GUIDE

This document will explain the entire structure for portal applications. This document will also give some brief explanation and an insight for common files and structure.

## Folders

The default folders in the root directory of the project will be:

* `config`
* `doc`
* `public`
* `views`

## files

The default root directory files are:

* `index.php` : 
* `.gitignore`
* `.htaccess`
* `composer.json`
* `install.sh`
* `{{funnyname.php}}` (for returning headers)
* `README.md`

### Config folder files

* `languages.yaml` : This file should contain all the language text translation mappings accordingly. For example: 

```yaml
# this is an example for a page title in html page document

html-page-title:
  explanation: "Page title"
  uz: "Endi O'zbekistonda! Faqat Mobiuz abonentlari uchun ommabop viktorina «Slovomaniya»  siz azizlarga taqdim etiladi!"
  ru: "Розыгрыш призов от компании Uzmobile"
  en: "Prize drawing from Uzmobile"
```

* `languages`