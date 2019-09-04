# A2Vhost
```
          _____      ___    _           _   
    /\   |__ \ \    / / |  | |         | |  
   /  \     ) \ \  / /| |__| | ___  ___| |_ 
  / /\ \   / / \ \/ / |  __  |/ _ \/ __| __|
 / ____ \ / /_  \  /  | |  | | (_) \__ \ |_ 
/_/    \_\____|  \/   |_|  |_|\___/|___/\__|

```

a2vhost 1.0.1 (c) 2019 Hein Thanth <br><br>
* Virtual Host Configuration File Generator for Apache2
* License under MIT
* Developed in PHP && bash

## Installation
``` bash
$ wget https://github.com/heinthanth/a2vhost/raw/master/dist/a2vhost-1.0.0_amd64.deb
$ sudo apt-get install ./a2vhost-1.0.0_amd64.deb
```
To Uninstall, simply
``` bash
$ sudo apt-get autoremove --purge a2vhost
```

## Usage
Create VirtualHost
``` bash
$ sudo a2vhost --root=/var/www/html/demo --domain=demo.local --ssl=false
```
To create with SSL
``` bash
$ sudo a2vhost --root=/var/www/html/demo --domain=demo.local --ssl=true
```
Remove VirtualHost
``` bash
$ sudo a2vhost --remove --root=/var/www/html/demo --domain=demo.local --ssl=false
```
To remove with SSL
``` bash
$ sudo a2vhost --remove --root=/var/www/html/demo --domain=demo.local --ssl=true
```

## Limitations
* Only available for Debian-based system till now
* May experience some bugs on some system
* Feel free to open issue.