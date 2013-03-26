#!/bin/sh
#
data_flg=0
db_user="db_user"
db_passwd="db_passwd"
db_name="iplist"
get_url="ftp://ftp.lacnic.net/pub/stats/lacnic/delegated-lacnic-latest"
get_file="delegated-lacnic-latest"
key="lacnic"
# Erase Table
/usr/bin/mysql --user="$db_user" --password="$db_passwd" $db_name <<eof
delete from iplist_trn where wariate='$key';
eof
db_insert(){
/usr/bin/mysql --user="$db_user" --password="$db_passwd" $db_name <<eof
  insert into iplist_trn
  (wariate,country,ip,kosu,wariate_year,jyokyo,netblock)
  values
  ('$wariate','$country','$ip',$kosu,$wariate_year,'$jyokyo','${temp}')
eof
}

cd /tmp
#APNIC
wget $get_url
if [ -f $get_file ]; then
echo "LACNIC-START"
	cat $get_file | sed -e 's/|/ /g' | while read wariate country ip4 ip kosu wariate_year jyokyo ;
	
	do
	if [ "$ip4" = "ipv4" ]; then
	if [ "$wariate_year" != "summary" ]; then
	wk_kosu=`expr $kosu - 1`
	wk_iprange=`ipcount $ip + $wk_kosu`
        for temp in ${wk_iprange[@]};do
            case "$temp" in
                *,*) break;;
                */*)
	            #echo "aaa${temp}"
		    db_insert;;
            esac
        done
	fi
	fi
	done
rm $get_file
data_flg=1
fi
if [ "$data_flg" = "1" ]; then
/usr/bin/mysql --user="$db_user" --password="$db_passwd" $db_name <<eof
delete from iplist where wariate='$key';
  insert into iplist
  (wariate,country,ip,kosu,wariate_year,jyokyo,netblock)
  select wariate,country,ip,kosu,wariate_year,jyokyo,netblock 
  from iplist_trn where wariate='$key';
eof

fi

