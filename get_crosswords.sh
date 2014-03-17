#!/bin/bash

cd /var/www/crosswords.linuxplicable.org/ini

curl -s http://www.theguardian.com/crosswords -o /tmp/crosswords

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

