<HTML>
<HEAD>

<title>PADME Database</title>

<style type="text/css">

   body {
     background: #FFFFCC;
   }
   a:link {
     text-decoration: none;
     color: blue;
   }
   a:visited {
     text-decoration: none;
     color: darkblue;
   }
   a:hover {
     text-decoration: underline;
     color: red;
   }

</style>

</HEAD>

<BODY>

<?php
require 'padme_query_scripts.php';
require 'padme_query_tools.php';

# Set default timezone for the package (required by PHP)
date_default_timezone_set('UTC');
?>

<TABLE width=100%>
<TR>
<TD ALIGN=LEFT><A HREF="http://padme.lnf.infn.it"><IMG SRC="http://dr.lnf.infn.it/wp-content/uploads/sites/20/2018/06/PADME_200.png" BORDER=0 ALIGN=left hspace="40"></TD>
<TD ALIGN=CENTER>
<?php
echo "<table>\n";
echo "\t<tr><td align=center colspan=2><H1>PADME DataBase</H1></td></tr>\n";
echo "\t<tr>\n";
echo "\t\t<th align=left><a href=\"",RUN_SCRIPT,"\">Runs</a></th>\n";
echo "\t\t<th align=right><a href=\"",PROD_SCRIPT,"\">Productions</a></th>\n";
echo "\t</tr>\n";
echo "</table>\n";
?>
</TD>
<TD ALIGN=RIGHT><A HREF="http://www.lnf.infn.it"><IMG SRC="http://w3.lnf.infn.it/wp-content/uploads/2015/03/logolnf_web.png" BORDER=0 ALIGN=right hspace="40"></A></TD>
</TR>
</TABLE>
