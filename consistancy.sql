#length of UPC fields. Disc ID's are 18
prompt "length of UPC fields. Disc ID's are 18 ";
select length(upc), count(*) from DVDPROFILER_dvd group by length(upc);
select formataspectratio, count(*) from DVDPROFILER_dvd group by formataspectratio;
select title,formatletterbox,format16x9 from DVDPROFILER_dvd where formatletterbox = 0 and format16x9 = 1;
select title,formataspectratio from DVDPROFILER_dvd where formatletterbox = 0 and formataspectratio != '1.33' and formataspectratio != '';
prompt "look for credited as ";
select substr(title,1,40),fullname,role from DVDPROFILER_dvd x,DVDPROFILER_dvd_actor a, DVDPROFILER_dvd_common_actor ca where x.id=a.id and ca.caid=a.caid and role like '%(as %' order by sorttitle;
prompt "mysql> ";
