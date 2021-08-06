#!/bin/sh
URI="https://api.github.com"
API_HEADER="Accept: application/vnd.github.v3+json"
AUTH_HEADER="Authorization: token ${GITHUB_TOKEN}"
PRNumber=$(jq --raw-output .pull_request.number "$GITHUB_EVENT_PATH")
flag=false
filesChanged=$(curl -sSL -H "${AUTH_HEADER}" -H "${API_HEADER}" "${URI}/repos/${GITHUB_REPOSITORY}/pulls/${PRNumber}/files?per_page=100" | jq --raw-output '.[] | {filename: .filename}| @base64')

bvt_ignore_files=".*test.*yml.* "

flag=false
for fc64 in $filesChanged; do
  fc="$(echo "$fc64" | base64 -d)"
  fileName=$(echo "$fc" | jq --raw-output '.filename')
  OIFS=$IFS
  IFS=","
  for pattern in $bvt_ignore_files
  do
    filesChangedArr=`echo "$fileName" | grep ${pattern} `
    len=${#filesChangedArr}
      if [ "$len" = "0" ]; then
        flag=false
      else
        flag=true
        break
      fi
  done
  IFS=$OIFS
  if [ "$flag" = "false" ]; then
    echo false
    exit 0
  fi
done
echo true
exit 0
