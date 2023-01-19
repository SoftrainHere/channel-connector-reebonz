## Laravel package for Reebonz

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

### How to join this project
1. update your local `dev` branch from remote `dev`(You may have to resolve conflicts)
2. checkout your local branch like `NMC-XXX` from `dev`
3. make your codes and commit your `NMC-XXX` branch
4. Checkout `dev`
5. check if your local `dev` is possible to merge or rebase with `NMC-XXX`(There shouldn't be no conflicts)
6. if possible, checkout `NMC-XXX`
7. push your `NMC-XXX` branch to remote
8. make `PR` to remote `dev` from remote `NMC-XXX`(PR `dev` <= `NMC-XXX`)

### DO:
- Keep your local `dev` `staging` branch same with remote `dev` `staging`
- Make sure to update your local `dev` from remote `dev`

  before you make merge/rebase test to your local `dev` from some local branch
- If there are any conflicts when you update your local `dev` and `staging` from remote,

  just click `accept theirs`


### DO NOT:
- Do not commit your local `composer.json` and `composer.lock` to any remote branch
- Do not merge or rebase remote `staging` into your `dev`, just keep your local `staging` and `dev` same with remotes
- Do not modify or update your `dev` branch directly, just update it from remote `dev`
- Do not push your commits to remote `staging` or `dev` branch directly(even if possible) 


 
