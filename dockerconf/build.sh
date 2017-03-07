if [ -z selenium-server.jar ]
then curl https://selenium-release.storage.googleapis.com/2.53/selenium-server-standalone-2.53.1.jar -o selenium-server.jar
fi
docker build -t pronav/dash .
