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

#INDY
for type in simple cryptic
do
    curl -s "http://www.independent.co.uk/extras/puzzles/crosswords/?crosswordType="$type -o /tmp/crosswordsindy
    datafile=`grep DATAFILE /tmp/crosswordsindy | awk -F= '{print $3}' | awk -F\" '{print $2}'`
    file=`basename $datafile`
    curl -s "http://www.independent.co.uk/$datafile" -o /tmp/$file
    date=`echo $file | sed 's/[sc]_\(..\)\(..\)\(..\)\.bin/\3\2\1/'`
    /usr/bin/php /var/www/crosswords/linuxplicable.org/make_indy_ini.php /tmp/`basename $datafile` > /var/www/crossword-repository/i$type-$date.ini
done


