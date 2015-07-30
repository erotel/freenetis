#!/bin/bash
################################################################################
# Script for debianization of FreenetIS
# (c) Ondrej Fibich, 2012
#
# Takes one or two arguments (version of package and package - if empty do it all)
# and it generates all FreenetIS packages to directory deb_packages.
#
################################################################################

if [ $# -lt 1 ]; then
    echo "Wrong arg count.. Terminating"
    exit 1
fi

NAMES=(freenetis freenetis-monitoring freenetis-redirection freenetis-ulogd freenetis-ssh-keys)
VERSION=$1

if [ $# -eq 2 ]; then
	NAMES=($2)
fi

# functions ####################################################################

function red_echo() {
	echo -e "\e[01;31m$1\e[0m"
}

function green_echo() {
	echo -e "\e[01;32m$1\e[0m"
}

# create dirs ##################################################################
rm -rf deb_packages
mkdir deb_packages

# call all debianization utils #################################################

for name in ${NAMES[*]}
do
	deb_sh=./$name/debianization.sh
	
	if [ -f "$deb_sh" ]; then
		./$deb_sh "$VERSION"

		if [ $? -eq 0 ]; then
			green_echo ">>>> [$name] debianized"
		else
			red_echo ">>>> [$name] an error occured during debianization"
		fi
	else
		red_echo ">>>> [$name] not debianized (debianization utility is missing)"
	fi
done

