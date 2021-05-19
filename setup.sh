#!/bin/bash

DEPLOYMENT_HTML="html"
DEPLOYMENT_SCHEMA="schema/main.sql"

grep -E "Debian|Ubuntu" /etc/issue.net > /dev/null

if [ $? -ne 0 ]; then
    echo "[!] Debian or Ubuntu distribution required"
    exit 1
fi

if [ ! -d ".git" ]; then
    echo "[!] Git cloned repository is missing"
    exit 1
fi

if [[ "$PWD" != "/var/www" ]]; then
    echo "[!] This should be run inside the '/var/www'"
    exit 1
fi

if [ ! -f "$DEPLOYMENT_SCHEMA" ]; then
    echo "[!] Deployment file '$DEPLOYMENT_SCHEMA' is missing"
    exit 1
fi

result=`apt-cache search --names-only '^php5$'`
if [ -z "$result" ] ; then
    DEBIAN_FRONTEND=noninteractive apt-get -qq -y install apache2 php libapache2-mod-php libapache2-mod-evasive nullmailer default-mysql-server default-mysql-client php-mysql php-gd php-xml php-simplexml openssh-server unattended-upgrades cron php-curl
else
    DEBIAN_FRONTEND=noninteractive apt-get -qq -y install apache2 php5 libapache2-mod-php5 libapache2-mod-evasive nullmailer mysql-server mysql-client php5-mysql php5-gd php5-xml php5-simplexml openssh-server unattended-upgrades cron php5-curl
fi

sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf
sed -i 's/Options Indexes.*/Options FollowSymLinks/g' /etc/apache2/apache2.conf

# Reference: https://www.brightbox.com/docs/guides/unattended-upgrades/
#            http://www.ubuntufree.com/easily-turn-on-automatic-security-updates-for-ubuntu-servers/
#            https://blog.mattbrock.co.uk/hardening-the-security-on-ubuntu-server-14-04/

cat > /etc/apt/apt.conf.d/20auto-upgrades << EOF
APT::Periodic::Update-Package-Lists "1";
APT::Periodic::Unattended-Upgrade "1";
EOF

touch /var/log/wtmp

if [ -f "/etc/php5/apache2/php.ini" ]; then
    PHP_INI="/etc/php5/apache2/php.ini"
elif [ -f "/etc/php/7.0/apache2/php.ini" ]; then
    PHP_INI="/etc/php/7.0/apache2/php.ini"
else
    PHP_INI=$(find /etc -iname php.ini 2>/dev/null | grep apache2)
fi

sed -i 's/^expose_php.*/expose_php = Off/g' $PHP_INI
sed -i 's/^html_errors.*/html_errors = Off/g' $PHP_INI
sed -i 's/^allow_url_fopen.*/allow_url_fopen = Off/g' $PHP_INI
sed -i 's/^allow_url_include.*/allow_url_include = Off/g' $PHP_INI
sed -i 's/^disable_functions.*/disable_functions = exec,passthru,system,proc_open,popen,curl_exec,curl_multi_exec,parse_ini_file,show_source/g' $PHP_INI

if [ ! -d "/var/log/mod_evasive" ]; then
    mkdir /var/log/mod_evasive
    chown -R www-data:www-data /var/log/mod_evasive
fi

cat > /etc/apache2/mods-available/evasive.conf << EOF
<IfModule mod_evasive20.c>
    DOSHashTableSize    3097
    DOSPageCount        5
    DOSSiteCount        50
    DOSPageInterval     1
    DOSSiteInterval     1
    DOSBlockingPeriod   10
    #DOSLogDir           "/var/log/mod_evasive"
</IfModule>
EOF

cat > /etc/apache2/conf-enabled/security.conf << EOF
ServerTokens Prod
ServerSignature Off
TraceEnable Off
Header unset ETag
FileETag None
EOF

# Note: expected memory footprint per worker-process is 21KB (RES)
cat > /etc/apache2/mods-enabled/mpm_prefork.conf << EOF
# Reference: https://www.liquidweb.com/kb/apache-performance-tuning-mpm-directives/
<IfModule mpm_prefork_module>
    MaxRequestWorkers   512
    ServerLimit         512
    MinSpareServers     100
    MaxSpareServers     200
    StartServers        100
</IfModule>
EOF

git checkout .

read -s -p "Enter new password for MySQL user 'ecsc' (press <Enter> for default): " NEW_PWD
echo

if [[ $NEW_PWD != ${NEW_PWD//[\']/} ]]; then
    echo "Single-quote (') character is unacceptable"
    exit 1
fi

if [[ -n "$NEW_PWD" ]]; then
    read -s -p "Confirm new password: " PWD_CONFIRM
    echo
    if [ $NEW_PWD == $PWD_CONFIRM ]; then
        sed -i "s/define.\"MYSQL_PASSWORD\".*/define(\"MYSQL_PASSWORD\", \"$NEW_PWD\");/g" "$DEPLOYMENT_HTML/includes/common.php"
        sed -i "s/IDENTIFIED BY.*/IDENTIFIED BY '$NEW_PWD';/g" "$DEPLOYMENT_SCHEMA"
    else
        echo "Passwords don't match"
        exit 1
    fi
fi

service mysql restart
mysql < $DEPLOYMENT_SCHEMA

chown -R www-data:www-data /var/www
chown -R root:root /var/www/setup.sh
chmod 700 /var/www/setup.sh

a2enmod -q headers
a2enmod -q evasive
a2enmod -q reqtimeout
a2enmod -q rewrite
service apache2 restart

jobs_file=$(mktemp)
crontab -l > $jobs_file
cat >> $jobs_file << "EOF"
*/1 * * * * test -f /var/www/.cron && php /var/www/.cron
EOF
crontab $jobs_file
rm $jobs_file
