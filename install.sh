#!/usr/bin/env bash

#//~ Assign service variables used in the script
service_group="abcvyz"
service_account="abcvyz"
service_name="{{service_name}}"
service_name_os="{{service_name}}"
service_timezone="Asia/Tashkent"
service_dir="/nannodit/{{service_name}}"
site_name="{{servuce_name}}"
log_dir="$service_dir/log"

#//~ Define common functions used in the script
function reboot_now 
{
    while true; do
        read -p "You must reboot for the changes is in effect, reboot now ? " yn
        case $yn in
            [Yy]* ) reboot;;
            [Nn]* ) exit;;
            * ) echo "Please answer yes or no.";;
        esac
    done
}


function check_package_installed () 
{
    is_installed="$(dpkg-query -W --showformat='${db:Status-Status}' $1)"
    if [[ -z $is_installed ]] || [[ $is_installed == "not-installed" ]];
    then
        echo "ERROR: $1 is not installed"
        exit
    fi
    
    echo "PASS: $1 is installed"  
}


function setup_recursive_group_access_to_folder ()
{
    setfacl -dR -m "g:$service_group:rwX" $1
    echo "INFO: Given recursive rw access to group '$service_group' for folder $1"
}

function check_user_existance ()
{
  if id "$1" >/dev/null 2>&1; then
    echo "PASS: User exists: $1"
  else
    echo "FAIL: User is absent: $1";
    exit 1
  fi
}

function check_service_active() 
{
  is_active=$(systemctl is-active $1)
  if [ "$is_active"="active" ];
  then
    echo "PASS: $1 is active"
  else
    echo "FAIL: $1 is not active, exiting"
    exit 1   
  fi
}

function check_group_existance ()
{
   if grep -q $1 /etc/group
    then
         echo "PASS: Group exists: $1"
    else
         echo "FAIL: Group is absent: $1"
         exit 1
    fi
}

function check_user_is_member_of_group ()
{
  if getent group $2 | grep -q "\b$1\b"; then
    echo "PASS: User '$1' is a member of the gorup '$2'"
  else
    echo "FAIL: User '$1' is NOT a member of the gorup '$2'"
    exit 1
  fi
}

function check_or_make_dir_with_ownership ()
{
  if [[ -d $1 ]];
  then
    echo "PASS: Dir exists: $1"
  else
    mkdir -p $1
    echo "INFO: Created dir: $1"
    created="yes"
  fi
#
  if [[ -n $created ]];
  then
    chown $2 $1
    echo "INFO: chowned dir: $2"
  else
    owner=$(ls -ld $1 | awk '{print $3":"$4}')
    if [[ $owner == $2 ]];
    then      
      echo "PASS: Dir owners match the request"
    else
      echo "ERROR: Dir current owners ($owner) are different then requested ($2); it should be manually fixed."
      exit 1
    fi
  fi
}

#//~ Apache
function setup_apache
{
    apache_site_config="$service_dir/config/$site_name.conf"
    #apache_site_envvars="$service_dir/config/$site_name.envvars" 
    echo "INFO : Adding the site config : $apache_site_config"
    #Below is necessary since apache config will use these envars
   # check_and_add_service_name_in_envar
    sudo ln -s "$apache_site_config" "/etc/apache2/sites-available/$site_name.conf"
    a2enmod rewrite
    a2ensite $site_name
    usermod -aG abcvyz www-data
    systemctl restart apache2
    echo "Apache process completed"
}

#//~ Group access to folders
function setup_recursive_service_group_access_to_folders
{
  setup_recursive_group_access_to_folder $service_dir
  setup_recursive_group_access_to_folder "/var/log/apache2/$service_name"
}

#//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
#//~ START
#//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

#//NOTE : Make php7.4 as default
arg_php73=true

#//~ Loop through arguments
for arg in "$@"; do
  #check
  if [[ "$arg" = --apache ]]; then
    arg_setup_apache=true
  
  #check
  elif [[ "$arg" = --php73 ]]; then
    arg_php73=true

  elif [[ "$arg" = --php74 ]]; then
    arg_php74=true


  elif [[ "$arg" = --all ]]; then
    #//NOTE This is a special case which will ignore all other arguments
    setup_recursive_service_group_access_to_folders
    setup_apache
    
    exit 1
  
  else
    echo ""
    echo "ERROR : Unknown option: $arg"
    echo ""
    exit
  fi

done


#//~ Required service and os package presence check

#//~ Show starting info
echo "INFO: Starting with:"
echo "  - service_group=$service_group"
echo "  - service_account=$service_account"
echo "  - service_name=$service_name"
echo "  - service_name_os=$service_name_os"
echo "  - service_timezone=$service_timezone"
echo "  - service_dir=$service_dir"
echo "  - site_name=$site_name"
echo "  - log_dir=$log_dir"
echo ""
echo "INFO: Checking preleminaries:"
echo ""

#//~ Group and account
check_user_existance "abcvyz"
check_group_existance "abcvyz"
check_user_is_member_of_group "abcvyz" "abcvyz"

#//~ ~ Apache2
check_package_installed "apache2"
check_service_active "apache2.service"

#//~ ~ PHP
if [[ "$arg_php73" = true ]]; 
then
  check_package_installed "php7.3"
  check_package_installed "php7.3-common"
  check_package_installed "php7.3-curl"
  check_package_installed "php7.3-opcache"
fi
if [[ "$arg_php74" = true ]]; 
then
  check_package_installed "php7.4"
  check_package_installed "php7.4-common"
  check_package_installed "php7.4-curl"
  check_package_installed "php7.4-opcache"
fi
check_package_installed "curl"
check_package_installed "git"
check_package_installed "composer"

echo ""
echo "INFO: All checks finished"
echo ""

#//~ Display instuctions if no argument passed.
if [ $# -eq 0 ]; then
    echo ""
    echo "No arguments provided. below are the options:"
    echo "  --apache : "
    echo ""
    echo "  --all : "
    echo ""
    echo "  --php73 : "
    echo ""
    echo "  --php74 : "
    echo ""
    exit 1
fi



#//~ Run the components

#//TODO : Be smarter; don't redo stuff if all is picked


if [[ "$arg_setup_apache" = true ]]; then
  setup_apache
fi

if [[ "$arg_setup_folders" = true ]]; then
  setup_recursive_service_group_access_to_folders
fi
