#!/bin/bash
set -euo pipefail
IFS=$'\n\t'

# This bash file validates the environment variables from .env.sample and production env variables.
# The environment variables should match in both the files


# All the variables are in A=B format with comments using '#'
# We strip the comments and store the env variables in memory.
PROJECT_ROOT=$( cd "$( dirname "${BASH_SOURCE[0]}" )/../" && pwd )
SAMPLE_ENV_PATH="${PROJECT_ROOT}/environment/.env.sample"
variables=$(cat $SAMPLE_ENV_PATH | sed 's/#.*//' | awk -F "=" '{print $1}' | sort | uniq)

# Store the prod env variables in memory
API_ROOT='/home/ubuntu/api/environment/.env'
prod_variables=$(cat $API_ROOT | sed 's/#.*//' | awk -F "=" '{print $1}' | sort | uniq)

var_diff=$(comm -23 <( echo "$variables" ) <( echo "$prod_variables" ))

# If diff is not empty and not null, print the diff and exit with failure
if [ ! -z "$var_diff" -a "$var_diff" != " " ]; then
    echo "ERROR: Variables do not match"
    echo $var_diff
    exit -1
else
    echo "Environment Validated"
    exit 0
fi;
