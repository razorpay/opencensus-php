#!/bin/bash

CURRENT_DIR=`pwd`
cd ~/Library/Containers/com.docker.docker/Data/database/
flush_file="com.docker.driver.amd64-linux/disk/full-sync-on-flush"
if test -s $flush_file
	then
		echo "Initialize complete"
	else
		git reset --hard 2>&1 >/dev/null
		mkdir -p com.docker.driver.amd64-linux/disk
		echo "false" > $ flush_file
		git add $flush_file && git commit -s -m "Disable Flushing" 2>&1 >/dev/null
		echo "Initialized Configurations. Force Restarting docker for Mac"
		killall com.docker.osx.hyperkit.linux
		sleep 10
		launchctl start com.docker.helper
		sleep 5
		echo "Please Check that Docker has been successfully restarted before proceeding further"
fi

cd $CURRENT_DIR

