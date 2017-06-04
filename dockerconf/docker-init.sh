#!/bin/bash
CURRENT_DIR=`pwd`
platform='unknown'
unamestr=`uname`
if [[ "$unamestr" == 'Linux' ]]; then
	echo "No changes required in Linux"
	exit 0
fi

if [[ "$unamestr" == 'Darwin' ]]; then
	echo "Checking required changes in Mac"
	cd ~/Library/Containers/com.docker.docker/Data/database
	git reset --hard 2>&1 >/dev/null

	echo -n "Current full-sync-on-flush setting: "
	flush_file="com.docker.driver.amd64-linux/disk/full-sync-on-flush"
	flush_file_content="`cat $flush_file`"
	echo $flush_file_content
	echo

	echo -n "Current on-flush setting: "
	on_flush_file="com.docker.driver.amd64-linux/disk/on-flush"
	on_flush_file_content="`cat $on_flush_file`"
	echo $on_flush_file_content
	echo

	if [ "$flush_file_content" = "false" ] && [ "$on_flush_file_content" = "none" ]; then
		echo "Already Initialized"
	else
		echo "false" > $flush_file
		echo "none" > $on_flush_file

		git add $flush_file
		git add $on_flush_file

		git commit -s -m "disable flushing" 2>&1 >/dev/null

		echo "Initialized Configurations. Force Restarting docker for Mac"
		killall com.docker.osx.hyperkit.linux
		sleep 10
		open /Applications/Docker.app
		sleep 5
		echo "Please Check that Docker has been successfully restarted before proceeding further"
	fi
fi

cd $CURRENT_DIR

