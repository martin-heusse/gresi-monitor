# gresi-monitor

- to customize : 
	- change base url in monitor-constants.php
	- change constants.php
	- create and fill ids.php with
		- rbusername (the username and password at Rbee)
		- rbpass
		- db_host (the database host, username, password, database name)
		- db_username
		- db_name
		- db_pwd
		- mailadmin (the admin email, for alerts)
		- loapikey : the LoRa Orange API key
- to start : once the above files are good, take you browser to http://<your.server>/admin.php, and press the button! (Subsequent calls are harmless...)
- check that the tables now exist
- execute recuptodb.php (either in a shell or in a browser) to retrieve the data from solar.pvmeter... And/or call recuptodbtic.php. For local tests, use a small $initialNbWeeks (1 or 2) defined in recuptodb.php, or it can take a while...

## Note:
recuptodb.php will retrieve all meters associated with your account (with the peak powers), so it works immediately. For LoRa devices, you need to put the deveui (in decimal) into the column deveui of table <tp>ticmeters, and set the peak_power to the appropriate values. (tp is the table prefix defined in constants.php)
