#!/bin/bash
export JAVA_CMD=java

if [ -z "$2" ]; then
	echo "Parameters not found, usage: ./run.sh domain relativepath"
	exit
fi

HOST=$1
REL_PATH=$2

echo "Starting tests on ${HOST}${REL_PATH}"

conc=(1 2 5 10 20)

rm -f ./output/summary*.xml
for num in ${conc[@]}
do
	echo "";
	echo "Executing tests with ${num} concurrent users"

	jmeter -n -t ./testplan.jmx -j ./output/jmeter.log -JCONCURRENT=${num} -JHOST="${HOST}" -JREL_PATH="${REL_PATH}"
done

./formatter.php

echo "Done."