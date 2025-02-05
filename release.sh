#!/bin/bash

# Extract the version number from the readme.txt file
VERSION=$(grep -m 1 '^Stable tag:' "readme.txt" | awk '{print $3}')

OUTPUT_ZIP="./dist/dubbot-$VERSION.zip"

zip -r "$OUTPUT_ZIP" ./* -x "*/.*" "release.sh" "dist/*"  # Exclude hidden files and dist

