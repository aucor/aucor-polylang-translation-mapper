# Aucor: Polylang Translation Mapper

**Contributors:** [Teemu Suoranta](https://github.com/TeemuSuoranta)

**Tags:** polylang, translations, import, mapper

**License:** GPLv2 or later

## Description

This is a tool that helps you connect all imported posts as translations for the multilingual WordPress plugin [Polylang](https://wordpress.org/plugins/polylang/).


Requirements for the plugin:

 * Posts should have a language (you can set them when importing for example with WP All Import)
 * Translations have a unique identifier key in post_meta


**Take backup of your database before doing this! Use at your own risk.**


## Installation

How-to use:

 * Import your content with WP All Import Pro (or something else) and check that requirements above are met
 * Take a backup of your database
 * Download plugin and activate (you will need Polylang active)
 * You will see UI in admin notice that shows you the post type and meta_id
 * If post type or master id are incorrect, change them in the code (lines 36 and 37)
 * Click the button "Start connecting translations"
 * Click through steps (50 posts at a time). Each step will take some time.
 * Deactivate and delete plugin when you have gone through all the steps


**Composer:**
```
$ composer aucor/aucor-polylang-translation-mapper
```
**With composer.json:**
```
{
  "require": {
    "aucor/aucor-polylang-translation-mapper": "*"
  },
  "extra": {
    "installer-paths": {
      "htdocs/wp-content/plugins/{$name}/": ["type:wordpress-plugin"]
    }
  }
}
```


## Issues and feature whishlist

**Issues:**

(No known issues, yet)

 **Feature whishlist:**

 * Maybe a UI to select post type and master_id
 * Show total updated instead of count for step
 * Add CLI support


## Changelog

### 0.1.0 - Github launch
 * It's working