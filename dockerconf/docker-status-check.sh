#!/bin/bash
url='http://0.0.0.0:'
port=$(docker ps | grep "razorpay:api" | awk -F" {2,}" '{print $6}' | cut -d ':' -f2 | awk -F '->' '{print $1}')

if [ -z "$port" ]; 
then
	echo "API Port Unavailable. No output from docker. Setting port to default value of 28080"
	port=28080
fi
url+=$port
condition=1
echo "Doing StatusCheck for URL:" $url
while [ $condition -eq 1 ]; do
	status=$(curl -s -I "$url" 2>/dev/null | head -n 1 | cut -d ' ' -f2)
	api_server_status=$(docker ps -a --filter status=running | grep "razorpay-api")
	echo "API Server status:" $api_server_status

	if [[ "$api_server_status" == "" ]]; then
		api_server_exited_status=$(docker ps -a --filter status=exited | grep "razorpay-api" | awk -F" {2,}" '{print $5}')
		echo "API server seems to have exited unexpectedly - $api_server_exited_status. Please check container logs for errors"
		echo "Using this command: docker logs razorpay-api"
		exit 1
	fi
	if [ -z "$status" ]; then
		echo "Database migrations ongoing. Waiting for server to startup and connect..."
		sleep 5
	else
		echo "API Server is operationally up at:" $url
		break
	fi
done
echo "API Server is operationally up at:" $url
