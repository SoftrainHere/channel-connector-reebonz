## Laravel package for FAVININT

### Requirements
- Laravel 9.X
- Nginx or Apache
- Php-fpm(above PHP 8.1.X)
- Mysql or Mariadb(Supporting innodb and virtual column)
- Redis (above 6.2.X)

### Directory structure 
```
.config
.resources
.routes
.src
     Console
     Handler
         Mapper
         ToChannel // class implemeted for core events
     Hooks
     Http
     Jobs
     Traits // Not all traits are used in production
     ChannelConnectorServiceProvider.php // Do not change filename
.tests
```
