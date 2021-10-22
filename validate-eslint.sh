#!/bin/bash

# checking for only green colored lines(newly added changes)
# comparing current pr changes to master

# grep regex will match following eslint disable comments only
# /* eslint-disable */
# /* eslint-disable*/
# /*eslint-disable */
# /* eslint-disable rule */
# /* eslint-disable rule*/


if [[ $(git diff $1 --color=always|perl -wlne 'print $1 if /^\e\[32m\+\e\[m\e\[32m(.*)\e\[m$/' | grep -E '^\/\*\s*eslint-disable(\s[a-z-]*)*\s?\*\/$') ]]; then
    echo "You have disabled ESLint checks for entire file(s). Please fix these errors instead!!"
    exit 1
else
    echo "ESlint checks validated successfully ✅"
fi
