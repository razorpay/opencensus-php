#!/bin/sh

export INCLUDE_GROUPS='@project=payments|@project=partner-dashboard'
yarn test:e2e 
