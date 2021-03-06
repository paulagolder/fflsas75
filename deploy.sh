#!/bin/bash

FILE="$(date '+%Y-%m-%d')serverupload.tar"

DIR="src" 

tar cf "$FILE" src

tar --append --file="$FILE"  "translations"
tar --append --file="$FILE"  "web/css"
tar --append --file="$FILE"  "web/js"
tar --append --file="$FILE"  "app/config/routing.yml"
tar --append --file="$FILE"  "app/config/security.yml"
tar --append --file="$FILE"  "app/config/services.yml"
tar --append --file="$FILE"  "app/Resources"


gzip "$FILE"  
