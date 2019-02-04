#!/bin/bash
url='http://0.0.0.0:'
port=`docker ps|grep "razorpay:api"|awk -F" {2,}" '{print $6}'|cut -d ':' -f2|awk -F '->' '{print $1}'`
if [ -z "$port" ] 
then
	echo "API Port Unavailable. No output from docker. Setting port to default value of 28080"
	port=28080
fi
url+=$port
condition=1
echo "Doing StatusCheck for URL:" $url
while [ $condition -eq 1 ]
do
	status=`curl -s -I "$url" 2>/dev/null | head -n 1|cut -d ' ' -f2`
	if [ -z "$status" ]
	then
		echo "Database migrations ongoing. Waiting for server to startup and connect..."
		sleep 5
	else
		echo "API Server is operationally up at:" $url
		break
	fi
done
echo "API Server is operationally up at:" $url
