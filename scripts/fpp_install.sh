#!/bin/bash

# fpp-zettle install script
echo "Installing Jukebox Plugin for FPP...."

echo "Writing config file...."

file=/home/fpp/media/config/plugin.fpp-jukebox.json

defalt_json=$(cat <<EOF
{
  "static_sequence": "",
  "ticker_other_info": "",
  "items": []
}
EOF
)

if [ -s "$file" ]
then
	echo " Config file exists and is not empty... continuing "
else
	echo " Config file does not exist, or is empty "
   	touch $file
	echo "$defalt_json" > /home/fpp/media/config/plugin.fpp-jukebox.json
	sudo chown fpp /home/fpp/media/config/plugin.fpp-jukebox.json
fi
