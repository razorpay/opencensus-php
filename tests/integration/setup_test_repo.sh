#!/bin/bash
# Copyright 2018 OpenCensus Authors
#
# Licensed under the Apache License, Version 2.0 (the "License");
# you may not use this file except in compliance with the License.
# You may obtain a copy of the License at
#
#     http://www.apache.org/licenses/LICENSE-2.0
#
# Unless required by applicable law or agreed to in writing, software
# distributed under the License is distributed on an "AS IS" BASIS,
# WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
# See the License for the specific language governing permissions and
# limitations under the License.

# A script for installing necessary software on CI systems.

if [ ! -z "${CIRCLE_PR_NUMBER}" ]; then
    PR_INFO=$(curl "https://api.github.com/repos/${CIRCLE_PROJECT_USERNAME}/${CIRCLE_PROJECT_REPONAME}/pulls/${CIRCLE_PR_NUMBER}")
    export BRANCH=$(echo $PR_INFO | jq -r .head.ref)
    export REPO=$(echo $PR_INFO | jq -r .head.repo.html_url)
elif [ ! -z "${CIRCLE_BRANCH}" ]; then
    export BRANCH=$CIRCLE_BRANCH
    export REPO=$CIRCLE_REPOSITORY_URL
else
    export BRANCH="master"
    export REPO="https://github.com/razorpay/opencensus-php"
fi
