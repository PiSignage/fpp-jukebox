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


placeholder_image =/home/fpp/media/images/placeholder.jpg
if [ -s "$placeholder_image" ]
	echo "Placehoolder image found no"
else
	echo "Placehoolder image not found, Copy placeholder image to images folder"
	sudo cp /home/fpp/media/plugins/fpp-julebox/img/placeholder.jpg /home/fpp/media/images/placeholder.jpg
	sudo chown fpp /home/fpp/media/images/placeholder.jpg
