#Stop api-web
/bin/rm -f /app/public/commit.txt && sleep 5 && /usr/sbin/apachectl -k graceful-stop
