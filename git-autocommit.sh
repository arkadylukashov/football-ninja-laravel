#!/bin/bash

if [ -z "$1" ]; then
        echo "Source directory needed"
        exit 1
fi

dir=$(readlink -f $1)
echo "Directory $dir"

cd $dir
git add -A
stagedCount=$(git status -s | wc -l)

if [ "$stagedCount" -gt "0" ]; then
        echo "Commiting & pushing detected changes"
        d=$(date)
        git commit -am "Autocommit on $d"
        git push
fi
