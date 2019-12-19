#!/bin/sh

$ALOHOMORA_BIN cast --region ap-south-1 --env "$APP_MODE" --app api "/usr/local/openresty/ngnix/sites/api.openresty.conf.j2"

#removing as sites only need valid files
rm /usr/local/openresty/ngnix/sites/api.openresty.conf.j2

ngnix