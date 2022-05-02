#!/bin/bash

#Limit fetch to this many file
LIMIT=200000

echo "---- Getting list (limit to $LIMIT) ---- `date` "

LIST=`aws s3 ls 's3://ps-scores/production/' | awk '{print $2}' | head -$LIMIT`

echo "---- Getting Files ----"
for GUID  in $LIST; do
	if [[ ! -d files/$GUID ]]; then
		echo -n "."
		mkdir files/$GUID

		resultsUrl=https://s3.amazonaws.com/ps-scores/production/${GUID}results.json
		scoresUrl=https://s3.amazonaws.com/ps-scores/production/${GUID}match_scores.json
		defUrl=https://s3.amazonaws.com/ps-scores/production/${GUID}match_def.json

		curl  --silent --compressed --output files/${GUID}match_def.json  $defUrl &
		curl  --silent --compressed --output files/${GUID}match_scores.json  $scoresUrl &
		curl  --silent --compressed --output files/${GUID}results.json  $resultsUrl &

		wait
	# else
	# 	echo -n "s"
	fi

done
