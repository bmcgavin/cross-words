#!/bin/bash

curl http://www.guardian.co.uk/crosswords/quick/12703 -o cw.html

ACROSS=`grep id=\"[0-9]+-across\" cw.html`
DOWN=`grep div cw.html | grep 'class\"down'`

echo $ACROSS

