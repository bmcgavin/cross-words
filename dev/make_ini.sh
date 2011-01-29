#!/bin/bash

curl http://www.guardian.co.uk/crosswords/quick/12703 -o cw.html

ACROSS=`grep div cw.html | grep 'class=\"across'`
DOWN=`grep div cw.html | grep 'class\"down'`

echo $ACROSS

