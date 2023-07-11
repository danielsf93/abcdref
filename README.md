# abcdref
adaptação de https://github.com/danielsf93/crossref-b que já funciona, mas obriga a todos a seguirem a regra de exportar xml para doi em dois idiomas


<br>


tentativa de replicação do plugin crossref. segue o erro:<br><br>

[Tue Jul 11 09:29:39 2023] 127.0.0.1:47384 Accepted
[Tue Jul 11 09:29:40 2023] PHP Fatal error:  Uncaught Error: Call to a member function setDeployment() on null in /home/ojs/ojsbase/3.2.1-1/fff/ojs-3.2.1-1/classes/plugins/PubObjectsExportPlugin.inc.php:363
Stack trace:
#0 /home/ojs/ojsbase/3.2.1-1/fff/ojs-3.2.1-1/classes/plugins/PubObjectsExportPlugin.inc.php(190): PubObjectsExportPlugin->exportXML()
#1 /home/ojs/ojsbase/3.2.1-1/fff/ojs-3.2.1-1/plugins/importexport/abcdref/AbcdRefExportPlugin.inc.php(263): PubObjectsExportPlugin->executeExportAction()
#2 /home/ojs/ojsbase/3.2.1-1/fff/ojs-3.2.1-1/classes/plugins/PubObjectsExportPlugin.inc.php(171): AbcdRefExportPlugin->executeExportAction()
#3 /home/ojs/ojsbase/3.2.1-1/fff/ojs-3.2.1-1/classes/plugins/DOIPubIdExportPlugin.inc.php(29): PubObjectsExportPlugin->display()
#4 /home/ojs/ojsbase/3.2.1-1/fff/ojs-3.2.1-1/lib/pkp/pages/management/PKPToolsHandler.inc.php(104): DOIPubIdExportPlugin->display()
#5 /home/ojs/ojsbase/3.2.1-1/fff/ojs-3.2.1-1/lib/pkp/classes/core/PKPRouter.inc.php(391): PKPToolsHandler->importexport()
#6 /home/ojs/ojsbase/3.2.1-1/fff/ojs- in /home/ojs/ojsbase/3.2.1-1/fff/ojs-3.2.1-1/classes/plugins/PubObjectsExportPlugin.inc.php on line 363
[Tue Jul 11 09:29:40 2023] 127.0.0.1:47384 [500]: POST /index.php/geousp/management/importexport/plugin/AbcdRefExportPlugin/exportSubmissions - Uncaught Error: Call to a member function setDeployment() on null in /home/ojs/ojsbase/3.2.1-1/fff/ojs-3.2.1-1/classes/plugins/PubObjectsExportPlugin.inc.php:363
Stack trace:
#0 /home/ojs/ojsbase/3.2.1-1/fff/ojs-3.2.1-1/classes/plugins/PubObjectsExportPlugin.inc.php(190): PubObjectsExportPlugin->exportXML()
#1 /home/ojs/ojsbase/3.2.1-1/fff/ojs-3.2.1-1/plugins/importexport/abcdref/AbcdRefExportPlugin.inc.php(263): PubObjectsExportPlugin->executeExportAction()
#2 /home/ojs/ojsbase/3.2.1-1/fff/ojs-3.2.1-1/classes/plugins/PubObjectsExportPlugin.inc.php(171): AbcdRefExportPlugin->executeExportAction()
#3 /home/ojs/ojsbase/3.2.1-1/fff/ojs-3.2.1-1/classes/plugins/DOIPubIdExportPlugin.inc.php(29): PubObjectsExportPlugin->display()
#4 /home/ojs/ojsbase/3.2.1-1/fff/ojs-3.2.1-1/lib/pkp/pages/management/PKPToolsHandler.inc.php(104): DOIPubIdExportPlugin->display()
#5 /home/ojs/ojsbase/3.2.1-1/fff/ojs-3.2.1-1/lib/pkp/classes/core/PKPRouter.inc.php(391): PKPToolsHandler->importexport()
#6 /home/ojs/ojsbase/3.2.1-1/fff/ojs- in /home/ojs/ojsbase/3.2.1-1/fff/ojs-3.2.1-1/classes/plugins/PubObjectsExportPlugin.inc.php on line 363
[Tue Jul 11 09:29:40 2023] 127.0.0.1:47384 Closing
