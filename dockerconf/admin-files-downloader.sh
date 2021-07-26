#!/bin/bash
vendor_pattern='vendor_a\.[a-zA-Z0-9]*\.js'
admin_pattern='admin\.[a-zA-Z0-9]*\.js'
css_pattern='admin\.[a-zA-Z0-9]*\.css'
content="$(curl https://betacdn.np.razorpay.in/dashboard/dist/admin-entry.js)"
# vendor js file
[[ $content =~ $vendor_pattern ]]
vendor_file=${BASH_REMATCH[0]}
# admin js file
[[ $content =~ $admin_pattern ]]
admin_file=${BASH_REMATCH[0]}
# css file
[[ $content =~ $css_pattern ]]
css_file=${BASH_REMATCH[0]}
# get all the files and output in proper path
wget https://betacdn.np.razorpay.in/dashboard/dist/admin-entry.js -P /app/public/dist
wget https://betacdn.np.razorpay.in/dashboard/dist/$vendor_file -P /app/public/dist
wget https://betacdn.np.razorpay.in/dashboard/dist/$admin_file -P /app/public/dist
wget https://betacdn.np.razorpay.in/dashboard/dist/css/$css_file -P /app/public/dist/css
