# query-relation-manager

![Packagist PHP Version Support](https://img.shields.io/packagist/php-v/smoren/query-relation-manager)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/Smoren/query-relation-manager-php/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/Smoren/query-relation-manager-php/?branch=master)
[![Coverage Status](https://coveralls.io/repos/github/Smoren/query-relation-manager-php/badge.svg?branch=master)](https://coveralls.io/github/Smoren/query-relation-manager-php?branch=master)
![Build and test](https://github.com/Smoren/query-relation-manager-php/actions/workflows/test_master.yml/badge.svg)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)


Реализует функционал получения данных из БД с отношениями "один к одному" и "один ко многим" с использованием одного 
запроса к БД, а также с учетом всех ограничений в запросе при получении отношений.

Может быть использован в любой ORM на php, потенциально совместим с любой реляционной СУБД.

Включает в себя пример реализации для чистого PDO без ORM.

Реализация для работы с **ActiveRecord** в виде расширения для **Yii2**:
https://github.com/Smoren/yii2-query-relation-manager
