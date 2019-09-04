#!/usr/bin/env php
<?php

if(posix_getuid() != 0) {
    echo "  Please run as root or sudo!".PHP_EOL;
    help();
    exit(0);
} else {
    if (!file_exists("/etc/a2vhost/")) {
        mkdir("/etc/a2vhost");
        mkdir("/etc/a2vhost/sites-available");
        mkdir("/etc/a2vhost/sites-enabled");
        mkdir("/etc/a2vhost/ssl/");
        mkdir("/etc/a2vhost/ssl/certs");
        mkdir("/etc/a2vhost/ssl/keys");
    }
    main($argv);
}

class vhost {
    var $ssl;
    var $root;
    var $domain;
    function __construct($ssl, $root, $domain) {
        $this->ssl = $ssl;
        $this->root = $root;
        $this->domain = $domain;
    }
    private function makeRoot() {
        if (!file_exists($this->root)) {
            mkdir($this->root, 0777, true);
        }
    }
    public function gen() {
        $this->makeRoot();
        if(in_array($this->ssl, ["true", "True"])) {
            sslgen($this->domain);
            echo "Self-signed SSL generated successfully. Enabling mod_ssl ... and Generating Configurations".PHP_EOL;
            shell_exec("a2enmod ssl");
            confgen($this->domain, realpath($this->root), true);
        }
        confgen($this->domain, realpath($this->root));
        echo "Configurations files generated successfully".PHP_EOL;
        echo "adding $this->domain to /etc/hosts file".PHP_EOL;
        writeHost($this->domain);
        echo "VirtualHost created successfully".PHP_EOL;
    }
    public function remove() {
        if(in_array($this->ssl, ["true", "True"])) {
            if(file_exists("/etc/a2vhost/ssl/certs/$this->domain.crt")) {
                unlink("/etc/a2vhost/ssl/certs/$this->domain.crt");
            }
            if(file_exists("/etc/a2vhost/ssl/keys/$this->domain.key")) {
                unlink("/etc/a2vhost/ssl/keys/$this->domain.key");
            }
            if(file_exists("/etc/a2vhost/sites-available/$this->domain-ssl.conf")) {
                unlink("/etc/a2vhost/sites-available/$this->domain-ssl.conf");
            }
        }
        if(file_exists("/etc/a2vhost/sites-available/$this->domain.conf")) {
            unlink("/etc/a2vhost/sites-available/$this->domain.conf");
        }
        if(file_exists($this->root)) {
            rmdir($this->root);
        }
        shell_exec("if grep -q '$this->domain' /etc/hosts; then sudo sed -i '/$this->domain/d' /etc/hosts; fi");
        echo "VirtualHost removed successfully".PHP_EOL;
    }
}
 
function main($argument) {
    if (requiredInput($argument)) {
        if (in_array("--help", $argument) || in_array("-h", $argument)) {
            help();
        } else if (in_array("--remove", $argument) || in_array("-r", $argument)) {
            $opt = getOption($argument);
            $vhost = new vhost($opt["ssl"], $opt["root"], $opt["domain"]);
            $vhost->remove();
        } else {
            $opt = getOption($argument);
            $vhost = new vhost($opt["ssl"], $opt["root"], $opt["domain"]);
            $vhost->gen();
            echo "Operation Complete. Restarting apache2 ...".PHP_EOL;
            shell_exec("sudo systemctl reload apache2");
            echo "Done! Thanks for using!".PHP_EOL;
        }
    } else {
        help();
    }
}

/* assets function start here */
function help() {
    showBanner();
    showUsage();
    exit(0);
}

function requiredInput($inputs) {

    $required = ["--domain=", "--root="];
    $pass = 0; $validSSL = false; $validDomain = false;
    foreach($inputs as $input) {
        if (startsWith($input, "--root=")) {
            $pass += 1;
        } else if (startsWith($input, "--domain=")) {
            $pass += 1;
            $raw = explode("=", $input);
            if (validateDomain($raw[1])) {
                $validDomain = true;
            }
        } else if (startsWith($input, "--ssl=")) {
            $raw = explode("=", $input);
            if (in_array($raw[1], ["true", "false", "True", "False"])) {
                $validSSL = true;
            }
        }
    }
    if($pass == count($required) && $validSSL && $validDomain) {
        return true;
    } else {
        return false;
    }
}

function getOption($arr) {
    foreach($arr as $index => $option) {
        if($index == 0) {
            continue;
        }
        $raw = explode("=", $option);
        if ($raw[0] == "--ssl") {
            $_ssl = $raw[1];
        }
        if ($raw[0] == "--root") {
            $_root = $raw[1];
        }
        if ($raw[0] == "--domain") {
            $_domain = $raw[1];
        }
    }
    return array('ssl' => $_ssl, 'root' => $_root, 'domain' => $_domain);
}

function validateDomain($domain) {
    return preg_match("/^(?:[-A-Za-z0-9]+\.)+[A-Za-z]{2,6}$/", $domain);
}


function startsWith ($string, $startString) { 
    $len = strlen($startString); 
    return (substr($string, 0, $len) === $startString); 
}

function sslgen($domain) {
    run("sudo openssl req -x509 -nodes -days 365 -newkey rsa:2048 -keyout /etc/a2vhost/ssl/keys/".$domain.".key -out /etc/a2vhost/ssl/certs/".$domain.".crt");
}

function run($cmd) {
    while (@ob_end_flush()) { /* end all output buffers if any */ }; 
    $proc = popen($cmd, 'r');
    while (!feof($proc)) {
        echo fread($proc, 4096);
        @flush();
    }
}

function writeHost($domain) {
    $file = fopen('/etc/hosts', 'a+');
    $exist = shell_exec("if grep -q '$domain' /etc/hosts; then echo \"true\"; else echo \"false\"; fi");
    
    if(trim($exist) == "false") {
        fwrite($file, "127.0.0.1       $domain\n");
    } else {
        // nothing
    }
}

function confgen($domain, $root, $ssl = false) {
    $name = "/etc/a2vhost/sites-available/$domain";
    if ($ssl == true) {
        $name .= "-ssl";
    }
    $file = fopen("$name.conf", "w");
    if($ssl == true) {
        fwrite($file, 
        "<IfModule mod_ssl.c>
            <VirtualHost _default_:443>
                ServerAdmin webmaster@localhost
        
                ServerName $domain:443
                ServerAlias $domain:443
                ServerAdmin admin@$domain
                DocumentRoot $root
                
                <Directory $root>
                    Options Indexes FollowSymLinks Includes ExecCGI
                    AllowOverride All
                    Require all granted
                </Directory>
        
                # Available loglevels: trace8, ..., trace1, debug, info, notice, warn,
                # error, crit, alert, emerg.
                # It is also possible to configure the loglevel for particular
                # modules, e.g.
                #LogLevel info ssl:warn
        
                ErrorLog \${APACHE_LOG_DIR}/error.log
                CustomLog \${APACHE_LOG_DIR}/access.log combined
        
                # For most configuration files from conf-available/, which are
                # enabled or disabled at a global level, it is possible to
                # include a line for only one particular virtual host. For example the
                # following line enables the CGI configuration for this host only
                # after it has been globally disabled with \"a2disconf\".
                #Include conf-available/serve-cgi-bin.conf
        
                #   SSL Engine Switch:
                #   Enable/Disable SSL for this virtual host.
                SSLEngine on
        
                #   A self-signed (snakeoil) certificate can be created by installing
                #   the ssl-cert package. See
                #   /usr/share/doc/apache2/README.Debian.gz for more info.
                #   If both key and certificate are stored in the same file, only the
                #   SSLCertificateFile directive is needed.
                SSLCertificateFile	/etc/a2vhost/ssl/certs/$domain.crt
                SSLCertificateKeyFile /etc/a2vhost/ssl/keys/$domain.key
        
                #   Server Certificate Chain:
                #   Point SSLCertificateChainFile at a file containing the
                #   concatenation of PEM encoded CA certificates which form the
                #   certificate chain for the server certificate. Alternatively
                #   the referenced file can be the same as SSLCertificateFile
                #   when the CA certificates are directly appended to the server
                #   certificate for convinience.
                #SSLCertificateChainFile /etc/apache2/ssl.crt/server-ca.crt
        
                #   Certificate Authority (CA):
                #   Set the CA certificate verification path where to find CA
                #   certificates for client authentication or alternatively one
                #   huge file containing all of them (file must be PEM encoded)
                #   Note: Inside SSLCACertificatePath you need hash symlinks
                #		 to point to the certificate files. Use the provided
                #		 Makefile to update the hash symlinks after changes.
                #SSLCACertificatePath /etc/ssl/certs/
                #SSLCACertificateFile /etc/apache2/ssl.crt/ca-bundle.crt
        
                #   Certificate Revocation Lists (CRL):
                #   Set the CA revocation path where to find CA CRLs for client
                #   authentication or alternatively one huge file containing all
                #   of them (file must be PEM encoded)
                #   Note: Inside SSLCARevocationPath you need hash symlinks
                #		 to point to the certificate files. Use the provided
                #		 Makefile to update the hash symlinks after changes.
                #SSLCARevocationPath /etc/apache2/ssl.crl/
                #SSLCARevocationFile /etc/apache2/ssl.crl/ca-bundle.crl
        
                #   Client Authentication (Type):
                #   Client certificate verification type and depth.  Types are
                #   none, optional, require and optional_no_ca.  Depth is a
                #   number which specifies how deeply to verify the certificate
                #   issuer chain before deciding the certificate is not valid.
                #SSLVerifyClient require
                #SSLVerifyDepth  10
        
                #   SSL Engine Options:
                #   Set various options for the SSL engine.
                #   o FakeBasicAuth:
                #	 Translate the client X.509 into a Basic Authorisation.  This means that
                #	 the standard Auth/DBMAuth methods can be used for access control.  The
                #	 user name is the `one line' version of the client's X.509 certificate.
                #	 Note that no password is obtained from the user. Every entry in the user
                #	 file needs this password: `xxj31ZMTZzkVA'.
                #   o ExportCertData:
                #	 This exports two additional environment variables: SSL_CLIENT_CERT and
                #	 SSL_SERVER_CERT. These contain the PEM-encoded certificates of the
                #	 server (always existing) and the client (only existing when client
                #	 authentication is used). This can be used to import the certificates
                #	 into CGI scripts.
                #   o StdEnvVars:
                #	 This exports the standard SSL/TLS related `SSL_*' environment variables.
                #	 Per default this exportation is switched off for performance reasons,
                #	 because the extraction step is an expensive operation and is usually
                #	 useless for serving static content. So one usually enables the
                #	 exportation for CGI and SSI requests only.
                #   o OptRenegotiate:
                #	 This enables optimized SSL connection renegotiation handling when SSL
                #	 directives are used in per-directory context.
                #SSLOptions +FakeBasicAuth +ExportCertData +StrictRequire
                <FilesMatch \"\.(cgi|shtml|phtml|php)$\">
                        SSLOptions +StdEnvVars
                </FilesMatch>
                <Directory /usr/lib/cgi-bin>
                        SSLOptions +StdEnvVars
                </Directory>
        
                #   SSL Protocol Adjustments:
                #   The safe and default but still SSL/TLS standard compliant shutdown
                #   approach is that mod_ssl sends the close notify alert but doesn't wait for
                #   the close notify alert from client. When you need a different shutdown
                #   approach you can use one of the following variables:
                #   o ssl-unclean-shutdown:
                #	 This forces an unclean shutdown when the connection is closed, i.e. no
                #	 SSL close notify alert is send or allowed to received.  This violates
                #	 the SSL/TLS standard but is needed for some brain-dead browsers. Use
                #	 this when you receive I/O errors because of the standard approach where
                #	 mod_ssl sends the close notify alert.
                #   o ssl-accurate-shutdown:
                #	 This forces an accurate shutdown when the connection is closed, i.e. a
                #	 SSL close notify alert is send and mod_ssl waits for the close notify
                #	 alert of the client. This is 100% SSL/TLS standard compliant, but in
                #	 practice often causes hanging connections with brain-dead browsers. Use
                #	 this only for browsers where you know that their SSL implementation
                #	 works correctly.
                #   Notice: Most problems of broken clients are also related to the HTTP
                #   keep-alive facility, so you usually additionally want to disable
                #   keep-alive for those clients, too. Use variable \"nokeepalive\" for this.
                #   Similarly, one has to force some clients to use HTTP/1.0 to workaround
                #   their broken HTTP/1.1 implementation. Use variables \"downgrade-1.0\" and
                #   \"force-response-1.0\" for this.
                # BrowserMatch \"MSIE [2-6]\" \
                #		nokeepalive ssl-unclean-shutdown \
                #		downgrade-1.0 force-response-1.0
        
            </VirtualHost>
        </IfModule>
        # vim: syntax=apache ts=4 sw=4 sts=4 sr noet
        ");
    } else {
    fwrite($file, 
        "<VirtualHost *:80>
            # The ServerName directive sets the request scheme, hostname and port that
            # the server uses to identify itself. This is used when creating
            # redirection URLs. In the context of virtual hosts, the ServerName
            # specifies what hostname must appear in the request's Host: header to
            # match this virtual host. For the default virtual host (this file) this
            # value is not decisive as it is used as a last resort host regardless.
            # However, you must set it for any further virtual host explicitly.
            #ServerName www.example.com

            ServerName $domain
            ServerAlias $domain
            ServerAdmin admin@$domain
            DocumentRoot $root
            
            <Directory $root>
                Options Indexes FollowSymLinks Includes ExecCGI
                AllowOverride All
                Require all granted
            </Directory>

            # Available loglevels: trace8, ..., trace1, debug, info, notice, warn,
            # error, crit, alert, emerg.
            # It is also possible to configure the loglevel for particular
            # modules, e.g.
            #LogLevel info ssl:warn

            ErrorLog \${APACHE_LOG_DIR}/error.log
            CustomLog \${APACHE_LOG_DIR}/access.log combined

            # For most configuration files from conf-available/, which are
            # enabled or disabled at a global level, it is possible to
            # include a line for only one particular virtual host. For example the
            # following line enables the CGI configuration for this host only
            # after it has been globally disabled with \"a2disconf\".
            #Include conf-available/serve-cgi-bin.conf
        </VirtualHost>
        # vim: syntax=apache ts=4 sw=4 sts=4 sr noet
        ");
    }
}

function showBanner() {
    echo '
            _____      ___    _           _   
      /\   |__ \ \    / / |  | |         | |  
     /  \     ) \ \  / /| |__| | ___  ___| |_ 
    / /\ \   / / \ \/ / |  __  |/ _ \/ __| __|
   / ____ \ / /_  \  /  | |  | | (_) \__ \ |_ 
  /_/    \_\____|  \/   |_|  |_|\___/|___/\__|
                                            '.PHP_EOL;
    echo '  Virtual Host Generator for Apache2'.PHP_EOL;
    echo '  a2vhost 1.0.2 - (c) Hein Thanth'.PHP_EOL;
    echo '  https://github.com/heinthanth/a2vhost'.PHP_EOL;
    echo PHP_EOL;
}

function showUsage() {
    echo "  Usage: a2vhost [ -options ]".PHP_EOL.PHP_EOL;
    echo "  Example: a2vhost --ssl=true --root=/var/www/demo --domain=demo.local".PHP_EOL.PHP_EOL;
    echo "  Common Option:".PHP_EOL;
    echo "      --remove :  add this option to remove virtualhost".PHP_EOL;
    echo "      --ssl    :  generate configuration for ssl-enabled vhost".PHP_EOL;
    echo "      --root   :  configure document root of vhost".PHP_EOL;
    echo "      --domain :  configure virtual domain name of vhost".PHP_EOL;
    echo PHP_EOL;
}

// remove line with sed -i '/domain/d' /etc/hosts

?>