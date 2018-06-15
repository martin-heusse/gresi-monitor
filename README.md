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
- to start : once the above files are good, navigate to admin.php, and press the button! (Subsequent calls are harmless...)
- check that the tables exist
- execute recuptodb.php to retrieve the data from solar.pvmeter... For local tests, use a small $initialNbWeeks (1 or 2), or it can take a while...
