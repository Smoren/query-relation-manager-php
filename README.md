# query-relation-manager

![Packagist PHP Version Support](https://img.shields.io/packagist/php-v/smoren/query-relation-manager)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/Smoren/query-relation-manager-php/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/Smoren/query-relation-manager-php/?branch=master)
[![Coverage Status](https://coveralls.io/repos/github/Smoren/query-relation-manager-php/badge.svg?branch=master)](https://coveralls.io/github/Smoren/query-relation-manager-php?branch=master)
![Build and test](https://github.com/Smoren/query-relation-manager-php/actions/workflows/test_master.yml/badge.svg)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

Implements the functionality of getting tree data from a database with one-to-one and one-to-many relationships using
only one select-query to the database with flexible conditions configuration.

**QueryRelationManager** can be integrated with any ORM system based on PHP and potentially compatible with any relational DBMS.

Current package includes an example of integration with raw PDO without ORM.

Also there is an implementation for **ActiveRecord** as [extension for **Yii2**](https://github.com/Smoren/yii2-query-relation-manager).

### How to install to your project
```
composer require smoren/query-relation-manager
```
