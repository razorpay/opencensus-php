#!/bin/bash
url='http://localhost:'
port=`docker ps|grep "razorpay:dashboard"|awk -F" {2,}" '{print $6}'|cut -d ':' -f2|awk -F '->' '{print $1}'`
if [ -z "$port" ] 
then
	echo "API Port Unavailable. No output from docker. Setting port to default value of 38080"
	port=38080
fi
url+=$port
condition=1
echo "Doing StatusCheck for URL:" $url
while [ $condition -eq 1 ]
do
	status=`curl -s -I "$url" 2>/dev/null | head -n 1|cut -d ' ' -f2`
	if [ -z "$status" ]
	then
		echo "Dashboard setup ongoing. Waiting for server to startup and connect..."
		sleep 30
	else
		echo "API Server is operationally up at:" $url
		break
	fi
done
echo "API Server is operationally up at:" $url
