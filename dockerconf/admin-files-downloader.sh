#!/bin/bash
vendor_pattern='vendor_a\.[a-zA-Z0-9]*\.js'
admin_pattern='admin\.[a-zA-Z0-9]*\.js'
css_pattern='admin\.[a-zA-Z0-9]*\.css'
base_url='https://betacdn.np.razorpay.in/'
# ADMIN_IMAGE is passed in the deployment file which is read from helmfile as user input , defaulting to deployed commit in beta if not present 
commit_id="${ADMIN_IMAGE:-false}"
if [[ $commit_id == false ]]; then
  echo "downloading beta admin files"
  base_url='https://betacdn.np.razorpay.in/dashboard/dist'
else
  echo 'downloading the commit id admin files'
  base_url="https://betacdn.np.razorpay.in/admin-dashboard/$commit_id"
fi
content="$(curl $base_url/admin-entry.js)"
# vendor js file
[[ $content =~ $vendor_pattern ]]
vendor_file=${BASH_REMATCH[0]}
# admin js file
[[ $content =~ $admin_pattern ]]
admin_file=${BASH_REMATCH[0]}
# css file
[[ $content =~ $css_pattern ]]
css_file=${BASH_REMATCH[0]}

# Ensure directories exist
mkdir -p /app/public/dist
mkdir -p /app/public/dist/css

# get all the files and output in proper path
wget $base_url/admin-entry.js -P /app/public/dist
wget $base_url/$vendor_file -P /app/public/dist
wget $base_url/$admin_file -P /app/public/dist
wget  $base_url/css/$css_file -P /app/public/dist/css

