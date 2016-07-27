;
; BIND data file for local loopback interface
;
$TTL	604800
@   IN  SOA	dns.mole.com. admin.mole.com. ( 2013040801 604800 86400	2419200	604800 )	
@   IN	NS	dns.mole.com.
@   IN  MX	10 www.mole.com.
dns.mole.com.	IN	A		192.168.1.33
www.mole.com.	IN	A		192.168.1.2 
admin.mole.com.	IN	CNAME	www.mole.com.
db.mole.com.	IN	CNAME	www.mole.com.
