#!/bin/bash

cd /var/www/crosswords.linuxplicable.org/ini

curl -s http://www.guardian.co.uk/crosswords -o /tmp/crosswords

for type in quick cryptic
do
	#echo $type
	ype=${type:1}
	#echo $ype
	number=`grep $type/ /tmp/crosswords | grep $ype | sed "s|.*$type\/\([0-9]\{5\}\)\".*|\1|g" | head -n 1`
	#echo $number
	/usr/bin/php /var/www/crosswords.linuxplicable.org/make_ini.php $type $number
	rm $type-latest.ini
	cp $type-$number.ini $type-latest.ini
done

#INDY
for type in simple cryptic
do
    curl -s "http://www.independent.co.uk/extras/puzzles/crosswords/?crosswordType="$type -o /tmp/crosswordsindy
    datafile=`grep DATAFILE /tmp/crosswordsindy | awk -F= '{print $3}' | awk -F\" '{print $2}'`
    curl -s "http://www.independant.co.uk/$datafile" -o /tmp/`basename $datafile`
done


