#!/bin/bash

cd public/dist;
rm css/icons.css &> /dev/null;

for i in `find . -name "*.js" | cut -d '/' -f2-`; do
  if [[ $i != *"-entry.js" ]]; then
    newname=${i::-2}`md5sum $i | cut -f1 -d' '`.js
    for entry in '*-entry.js'; do
      sed -i s#$i#$newname# $entry
    done
    mv $i $newname
  fi
done

for i in `find . -name "*.css" | cut -d '/' -f2-`; do
  newname=${i::-3}`md5sum $i | cut -f1 -d' '`.css
  for entry in '*-entry.js'; do
    sed -i s#$i#$newname# $entry
  done
  mv $i $newname
done
