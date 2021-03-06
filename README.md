# graylog-cli

Simple Graylog client by CLI.


#### Configuration
Copy .env.dist file to .env and edit the file. Search theses variables and change them :
 - GRAYLOG_HOST
 - GRAYLOG_USER
 - GRAYLOG_PASSWORD


After that, you can use theses commands :
 - ``bin/console graylog:streams``
 - ``bin/console graylog:version``
 - ``bin/console graylog:fetch``
 - ``bin/console graylog:tail``
 
Use -h for each command.


#### Examples 

Graylog "tail -f" like :
`` 
bin/console graylog:tail -f <stream-id> 
`` 

Graylog "tail -f" with search :
`` 
bin/console graylog:tail -f <stream-id> --search="level:<5"
`` 

Search between dates :
``
bin/console graylog:fetch <stream-id> --dateFrom="YYYY-MM-DD HH:ii:ss" --dateTo="YYYY-MM-DD HH:ii:ss"
``

Search with specific query (use Lucene) :
``
bin/console graylog:fetch <stream-id> --search="level:<5"
``