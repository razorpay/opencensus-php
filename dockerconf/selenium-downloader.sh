if [ ! -f selenium-server.jar ]; then
	echo "Downloading missing selenium jar"
    curl https://selenium-release.storage.googleapis.com/2.53/selenium-server-standalone-2.53.1.jar -o selenium-server.jar
fi
