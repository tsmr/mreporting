<?php
/*
 * @version $Id: HEADER 15930 2011-10-30 15:47:55Z tsmr $
 -------------------------------------------------------------------------
 Mreporting plugin for GLPI
 Copyright (C) 2003-2011 by the mreporting Development Team.

 https://forge.indepnet.net/projects/mreporting
 -------------------------------------------------------------------------

 LICENSE

 This file is part of mreporting.

 mreporting is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 mreporting is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with mreporting. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

class PluginMreportingCommon extends CommonDBTM {
   static $rightname = 'statistic';

   /**
    * Returns the type name with consideration of plural
    *
    * @param number $nb Number of item(s)
    * @return string Itemtype name
    */
   public static function getTypeName($nb = 0) {
      return __("More Reporting", 'mreporting');
   }

   public static function canCreate() {
      return false;
   }

   static function getMenuContent() {
      global $CFG_GLPI;

      $menu = parent::getMenuContent();

      $img_db = "<img src='".$CFG_GLPI["root_doc"]."/plugins/mreporting/pics/dashboard.png'
                           title='".__("Dashboard", 'mreporting')."' 
                           alt='".__("Dashboard", 'mreporting')."'>";
      $img_ct   = "<img src='".$CFG_GLPI["root_doc"]."/plugins/mreporting/pics/list_dashboard.png'
                           title='".__("Reports list", 'mreporting')."' 
                           alt='".__("Reports list", 'mreporting')."'>";
      $url_central   = "/plugins/mreporting/front/central.php";
      $url_dashboard = "/plugins/mreporting/front/dashboard.php";

      $menu['page'] = $url_central;
      if(PluginMreportingDashboard::CurrentUserHaveDashboard()) {
         $menu['page'] = $url_dashboard;
      }

      $menu['options']['dashboard']['page']            = $url_dashboard;
      $menu['options']['dashboard']['title']           = __("Dashboard", 'mreporting');
      $menu['options']['dashboard']['links'][$img_db]  = $url_dashboard;
      $menu['options']['dashboard']['links'][$img_ct]  = $url_central;
      if (PluginMreportingConfig::canCreate()) {
         $menu['options']['dashboard']['links']['config'] = PluginMreportingConfig::getSearchURL(false);
      }

      $menu['options']['dashboard_list']               = $menu['options']['dashboard'];
      $menu['options']['dashboard_list']['page']       = $url_central;
      $menu['options']['dashboard_list']['title']      = __("Reports list", 'mreporting');

      $menu['options']['config']['title']              = PluginMreportingConfig::getTypeName(2);
      $menu['options']['config']['page']               = PluginMreportingConfig::getSearchURL(false);
      $menu['options']['config']['links']              = $menu['options']['dashboard']['links'];
      $menu['options']['config']['links']['search']    = PluginMreportingConfig::getSearchURL(false);
      if (PluginMreportingConfig::canCreate()) {
         $menu['options']['config']['links']['add']    = PluginMreportingConfig::getFormURL(false);
      }

      return $menu;
   }


   const MNBSP = "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                  &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                  &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                  &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                  &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                  &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";

   /**
    * Parsing all classes
    * Search all class into inc folder
    * @params
   */
   static function parseAllClasses($inc_dir) {

      $classes = array();
      $matches = array();

      if ($handle = opendir($inc_dir)) {
         while (false !== ($entry = readdir($handle))) {
            if ($entry != "." && $entry != "..") {
               $fcontent = file_get_contents($inc_dir."/".$entry);
               if (preg_match("/class\s(.+)Extends PluginMreporting.*Baseclass/i",
                     $fcontent, $matches)) {
                  $classes[] = trim($matches[1]);
               }
            }
         }
      }

      return $classes;
   }

   /**
    * Get all reports from parsing class
    *
    * @params
   */

   function getAllReports($with_url = true, $params=array()) {
      global $LANG;

      $reports = array();

      $inc_dir = GLPI_ROOT."/plugins/mreporting/inc";
      $pics_dir = "../pics";

      //parse inc dir to search report classes
      $classes = self::parseAllClasses($inc_dir);

      sort($classes);
      if (isset($params['classname'])
            && !empty($params['classname'])) {
         $classes = array();
         $classes[] = $params['classname'];

      }

      //construct array to list classes and functions
      foreach($classes as $classname) {
         $i = 0;
         if (!class_exists($classname)) {
            continue;
         }

         //scn = short class name
         $scn = str_replace('PluginMreporting', '', $classname);
         if (isset($LANG['plugin_mreporting'][$scn]['title'])) {
            $title = $LANG['plugin_mreporting'][$scn]['title'];

            $functions = get_class_methods($classname);

            foreach($functions as $f_name) {
               $ex_func = preg_split('/(?<=\\w)(?=[A-Z])/', $f_name);
               if ($ex_func[0] != 'report') continue;

               if (isset($LANG['plugin_mreporting'][$scn][$f_name])) {
                  $gtype      = strtolower($ex_func[1]);
                  $title_func = $LANG['plugin_mreporting'][$scn][$f_name]['title'];
                  $category_func = '';
                  if (isset($LANG['plugin_mreporting'][$scn][$f_name]['category']))
                     $category_func = $LANG['plugin_mreporting'][$scn][$f_name]['category'];

                  if (isset($LANG['plugin_mreporting'][$scn][$f_name]['desc']))
                     $des_func = $LANG['plugin_mreporting'][$scn][$f_name]['desc'];
                  else {
                     $des_func = "";
                  }
                  $url_graph  = "graph.php?short_classname=$scn".
                     "&amp;f_name=$f_name&amp;gtype=$gtype";
                  $min_url_graph  = "front/graph.php?short_classname=$scn".
                     "&amp;f_name=$f_name&amp;gtype=$gtype";

                  $reports[$classname]['title'] = $title;
                  $reports[$classname]['functions'][$i]['function'] = $f_name;
                  $reports[$classname]['functions'][$i]['title'] = $title_func;
                  $reports[$classname]['functions'][$i]['desc'] = $des_func;
                  $reports[$classname]['functions'][$i]['category_func'] = $category_func;
                  $reports[$classname]['functions'][$i]['pic'] = $pics_dir."/chart-$gtype.png";
                  $reports[$classname]['functions'][$i]['gtype'] = $gtype;
                  $reports[$classname]['functions'][$i]['short_classname'] = $scn;
                  $reports[$classname]['functions'][$i]['is_active'] = false;

                  $config = new PluginMreportingConfig();
                  if ($config->getFromDBByFunctionAndClassname($f_name,$classname)) {
                     if ($config->fields['is_active'] == 1) {
                        $reports[$classname]['functions'][$i]['is_active'] = true;
                        $reports[$classname]['functions'][$i]['id'] = $config->fields['id'];
                     }
                     $reports[$classname]['functions'][$i]['right'] = READ;
                     if (isset($_SESSION['glpiactiveprofile'])) {
                        $reports[$classname]['functions'][$i]['right'] = PluginMreportingProfile::canViewReports($_SESSION['glpiactiveprofile']['id'],$config->fields['id']);
                     }
                  }

                  if ($with_url) {
                     $reports[$classname]['functions'][$i]['url_graph'] = $url_graph;
                     $reports[$classname]['functions'][$i]['min_url_graph'] = $min_url_graph;
                  }

                  $i++;
               }
            }
         }
      }

      return $reports;
   }

   /**
    * Show list of activated reports
    *
    * @param array $opt : short_classname,f_name,gtype,rand
   */
   static function title($opt) {
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr><th colspan='2'>".__("Select statistics to be displayed")."&nbsp;:</th></tr>";
      echo "<tr class='tab_bg_1'><td class='center'>";
      echo self::getSelectAllReports(true);
      echo "</td>";
      echo "</tr>";
      echo "</table>";
   }

   static function getSelectAllReports($onchange = false, $setIdInOptionsValues = false) {
      $select = "";
      $js_onchange = "";
      if ($onchange) {
         $js_onchange = " onchange='window.location.href=this.options[this.selectedIndex].value'";
      }

      $common = new self;
      $reports = $common->getAllReports(true);

      if (count($reports) > 0) {
         $select.= "<select name='report' $js_onchange>";
         $select.= "<option value='-1' selected>".Dropdown::EMPTY_VALUE."</option>";

         foreach($reports as $classname => $report) {
            $graphs = array();
            foreach($report['functions'] as $function) {
               if ($function['is_active']) {
                  $graphs[$function['category_func']][] = $function;
               }
            }

            foreach ($graphs as $cat => $graph) {
               $remove = true;
               foreach ($graph as $key => $value) {
                  if ($value['right']) {
                     $remove = false;
                  }
               }
               if ($remove) {
                  unset($graphs[$cat]);
               }
            }

            if (count($graphs) > 0) {
               $select.= "<optgroup label=\"".$report['title']."\">";
               foreach($graphs as $cat => $graph) {
                  if (count($graph) > 0) {
                     $select.= "<optgroup label=\"&nbsp;&nbsp;&nbsp;$cat\">";
                     foreach($graph as $key => $value) {
                        if ($value['right']) {
                            if ($value['is_active']) {
                                $comment = "";
                                if (isset($value["desc"])) {
                                    $comment = $value["desc"];
                                }
                                $option_value = $value["url_graph"];
                                if ($setIdInOptionsValues) {
                                 $option_value = $value['id'];
                                }
                                $icon = self::getIcon($value['function']);
                                $select.= "<option value='$option_value' title=\"". Html::cleanInputText($comment).
                                          "\">&nbsp;&nbsp;&nbsp;".$icon."&nbsp;".
                                          $value["title"]."</option>";
                            }
                        }
                     }
                     $select.= "</optgroup>";
                  }
               }
               $select.= "</optgroup>";
            }
         }
         $select.= "</select>";
      }

      return $select;
   }

   /**
    * parse All class for list active reports
    * and display list
   */

   function showCentral($params) {
      global $CFG_GLPI;

      $reports = $this->getAllReports(true, $params);

      if ($reports === false) {
         echo "<div class='center'>".__("No report is available !", 'mreporting')."</div>";
         return false;
      }

      echo "<table class='tab_cadre_fixe' id='mreporting_functions'>";

      foreach($reports as $classname => $report) {
         $i = 0;
         $nb_per_line = 2;
         $graphs = array();
         foreach($report['functions'] as $function) {
            if ($function['is_active']) {
               $graphs[$classname][$function['category_func']][] = $function;
            }
         }

         $count = 0;
         if (isset($graphs[$classname])) {
            foreach($graphs[$classname] as $cat => $graph) {
               if(self::haveSomeThingToShow($graph)){
                  echo "<tr class='tab_bg_1'><th colspan='4'>".$cat."</th></tr>";
                  foreach($graph as $k => $v) {
                     if($v['right'] && $v['is_active']) {
                        if ($i%$nb_per_line == 0) {
                           if ($i != 0) {
                              echo "</tr>";
                           }
                           echo "<tr class='tab_bg_1' valign='top'>";
                        }

                        echo "<td>";
                        echo "<a href='".$v['url_graph']."'>";
                        echo "<img src='".$v['pic']."' />&nbsp;";
                        echo $v['title'];
                        echo "</a>";
                        echo"</td>";
                        $i++;
                     }

                     $count++;
                     if ($i%$nb_per_line > 0) {
                         $count++;
                     }
                  }

                  while ($i%$nb_per_line != 0) {
                     echo "<td>&nbsp;</td>";
                     $i++;
                  }
               }
            }
         }
         echo "</tr>";

         if (isset($graphs[$classname]) && $count>0) {
            $height = 200;
            $height += 30*$count;
            echo "<tr class='tab_bg_1'>";
            echo "<th colspan='2'>";
            echo "<div class='f_right'>";
            echo __("Export")." : ";
            echo "<a href='#' onClick=\"var w = window.open('popup.php?classname=$classname' ,'glpipopup', ".
                  "'height=$height, width=1000, top=100, left=100, scrollbars=yes'); w.focus();\">";
            echo "ODT";
            echo "</a></div>";
            echo "</th>";
            echo "</tr>";

         } else {
            echo "<tr class='tab_bg_1'>";
            echo "<th colspan='2'>";
            echo __("No report is available !", 'mreporting');
            echo "</th>";
            echo "</tr>";
         }
      }


      echo "</table>";
   }

    static function haveSomeThingToShow($graph){
        foreach($graph as $k => $v) {
            if($v['right']){
                return true;
            }
        }
        return false;
    }

   /**
    * show Export Form from popup.php
    * for odt export
   */

   function showExportForm($opt) {
      $classname = $opt["classname"];
      if ($classname) {
         echo "<div align='center'>";

         echo "<form method='POST' action='export.php?switchto=odtall&classname=".$classname."'
                     id='exportform' name='exportform'>\n";

         echo "<table class='tab_cadre_fixe'>";

         echo "<tr><th colspan='4'>";
         echo __("No report is available !", 'mreporting');
         echo "</th></tr>";

         $reports = $this->getAllReports(false, $opt);

         foreach($reports as $class => $report) {
            $i = 0;
            $nb_per_line = 2;
            $graphs = array();
            foreach($report['functions'] as $function) {
               if ($function['gtype'] === "sunburst") continue;
               if ($function['is_active']) {
                  $graphs[$classname][$function['category_func']][] = $function;
               }
            }

            foreach($graphs[$classname] as $cat => $graph) {
               echo "<tr class='tab_bg_1'><th colspan='4'>".$cat."</th></tr>";
               foreach($graph as $k => $v) {

                  if ($v['is_active']) {
                     if ($i%$nb_per_line == 0) {
                        if ($i != 0) {
                           echo "</tr>";
                        }
                        echo "<tr class='tab_bg_1'>";
                     }

                     echo "<td>";
                     echo "<input type='checkbox' name='check[" . $v['function'].$classname . "]'";
                     if (isset($_POST['check']) && $_POST['check'] == 'all')
                        echo " checked ";
                     echo ">";
                     echo "</td>";
                     echo "<td>";
                     echo "<img src='".$v['pic']."' />&nbsp;";
                     echo $v['title'];
                     echo "</td>";
                     $i++;
                  }
               }

               while ($i%$nb_per_line != 0) {
                  echo "<td width='10'>&nbsp;</td>";
                  echo "<td>&nbsp;</td>";
                  $i++;
               }
            }
            echo "</tr>";
         }

         echo "<tr class='tab_bg_2'>";
         echo "<td colspan ='4' class='center'>";
         echo "<div align='center'>";
         echo "<table><tr class='tab_bg_2'>";
         echo "<td>";
         echo __("Begin date");
         echo "</td>";
         echo "<td>";
         $date1 =  strftime("%Y-%m-%d", time() - (30 * 24 * 60 * 60));
         Html::showDateFormItem("date1",$date1,true);
         echo "</td>";
         echo "<td>";
         echo __("End date");
         echo "</td>";
         echo "<td>";
         $date2 =  strftime("%Y-%m-%d");
         Html::showDateFormItem("date2",$date2,true);
         echo "</td>";
         echo "</tr>";
         echo "</table>";
         echo "</div>";

         echo "</td>";
         echo "</tr>";

         echo "</table>";
         Html::openArrowMassives("exportform", true);

         $option[0] = __("Without data", 'mreporting');
         $option[1] = __("With data", 'mreporting');
         Dropdown::showFromArray("withdata", $option, array());
         echo "&nbsp;";
         echo "<input type='button' id='export_submit'  value='".__("Export")."' class='submit'>";
         echo "</td></tr>";
         echo "</table>";
         Html::closeForm();
         echo "</div>";

         echo "<script type='text/javascript'>
            $('#export_submit').on('click', function () {
               //get new crsf
               $.ajax({
                  url: '../ajax/get_new_crsf_token.php'
               }).done(function(token) {
                  $('#export_form input[name=_glpi_csrf_token]').val(token);
                  document.getElementById('exportform').submit();
               });
            });
         </script>";
      }
   }

   /**
    * exit from grpah if no @params detected
    *
    * @params
   */

   function initParams($params, $export = false) {
      if(!isset($params['classname'])) {
         if (!isset($params['short_classname'])) exit;
         if (!isset($params['f_name'])) exit;
         if (!isset($params['gtype'])) exit;
      }

      return $params;
   }

   /**
    * init Params for graph function
    *
    * @params
   */

   static function initGraphParams($params) {
      $crit        = array();

      // Default values of parameters
      $raw_datas   = array();
      $title       = "";
      $desc        = "";
      $root        = "";

      $export      = false;
      $opt         = array();

      foreach ($params as $key => $val) {
         $crit[$key]=$val;
      }

      return $crit;
   }

   /**
    * show Graph : Show graph
    *
    * @params $options ($opt, export)
    * @params $opt (classname, short_classname, f_name, gtype)
   */

   function showGraph($opt, $export = false) {
      global $LANG, $CFG_GLPI;

      if (!isset($opt['hide_title'])) {
         self::title($opt);
         $opt['hide_title'] = false;
      }

      if (!isset($opt['width'])) {
         $opt['width'] = false;
      }

      //check the format display charts configured in glpi
      $opt = $this->initParams($opt, $export);
      $config = PluginMreportingConfig::initConfigParams($opt['f_name'],
         "PluginMreporting".$opt['short_classname']);

      if ($config['graphtype'] == 'PNG' ||
            $config['graphtype'] == 'GLPI' && $CFG_GLPI['default_graphtype'] == 'png') {
         $graph = new PluginMreportingGraphpng();
      } elseif ($config['graphtype'] == 'SVG' ||
            $config['graphtype'] == 'GLPI' && $CFG_GLPI['default_graphtype'] == 'svg') {
         $graph = new PluginMreportingGraph();
      }

      //generate default date
      if (!isset($_SESSION['mreporting_values']['date1'.$config['randname']])) {
         $_SESSION['mreporting_values']['date1'.$config['randname']] = strftime("%Y-%m-%d",
            time() - ($config['delay'] * 24 * 60 * 60));
      }
      if (!isset($_SESSION['mreporting_values']['date2'.$config['randname']])) {
         $_SESSION['mreporting_values']['date2'.$config['randname']] = strftime("%Y-%m-%d");
      }

      // save/clear selectors
      if (isset($opt['submit'])) {
         PluginMreportingCommon::saveSelectors($opt['f_name'], $config);
      } elseif (isset($opt['reset'])) {
         PluginMreportingCommon::resetSelectorsForReport($opt['f_name']);
      }
      PluginMreportingCommon::getSelectorValuesByUser();

      //dynamic instanciation of class passed by 'short_classname' GET parameter
      $classname = 'PluginMreporting'.$opt['short_classname'];
      $obj = new $classname($config);

      //dynamic call of method passed by 'f_name' GET parameter with previously instancied class
      $datas = $obj->$opt['f_name']($config);

      //show graph (pgrah type determined by first entry of explode of camelcase of function name
      $title_func = $LANG['plugin_mreporting'][$opt['short_classname']][$opt['f_name']]['title'];
      $des_func = "";
      if (isset($LANG['plugin_mreporting'][$opt['short_classname']][$opt['f_name']]['desc']))
        $des_func = $LANG['plugin_mreporting'][$opt['short_classname']][$opt['f_name']]['desc'];

      $opt['class'] = $classname;
      $opt['withdata'] = 1;
      $params = array("raw_datas"   => $datas,
                       "title"      => $title_func,
                       "desc"       => $des_func,
                       "export"     => $export,
                       "opt"        => $opt);
      return $graph->{'show'.$opt['gtype']}($params, $opt['hide_title'], $opt['width']);
   }


   static function dropdownExt($options = array()) {
      global $DB, $CFG_GLPI;

      $p['myname']                  = '';
      $p['value']                   = "";
      $p['ajax_page']               = '';
      $p['class']                   = '';
      $p['span']                    = '';
      $p['gtype']                   = '';
      $p['show_graph']              = '';
      $p['randname']                = '';
      foreach ($options as $key => $value) {
         $p[$key] = $value;
      }

      echo "<select name='switchto' id='".$p['myname']."'>";

      $elements[0] = Dropdown::EMPTY_VALUE;
      if ($p['gtype'] !== "sunburst") {
         $elements["odt"] = "ODT";
      }
      $elements["csv"] = "CSV";
      if ($p['show_graph']) {
         $elements["png"] = "PNG";
         $elements["svg"] = "SVG";
      }
      foreach ($elements as $key => $val) {
         echo "<option value='".$key."'>".$val.
                 "</option>";
      }

      echo "</select>";

      $params = array ('span' => $p['span'],
                        'ext' => '__VALUE__',
                        'randname' => $p['randname']);

      Ajax::updateItemOnSelectEvent($p['myname'], $p['span'],
                                  $p['ajax_page'],
                                  $params);
   }

   /**
    * end Graph : Show graph datas array, setup link, export
    *
    * @params $options ($opt, export, datas, unit, labels2, flip_data)
   */

   static function endGraph($options,$dashboard = false) {
      global $CFG_GLPI;

      $opt        = array();
      $export     = false;
      $datas      = array();
      $unit       = '';
      $labels2    =  array();
      $flip_data  = false;

      foreach ($options as $k => $v) {
         $$k=$v;
      }

      $randname = false;
      if (isset($opt['randname']) && $opt['randname'] !== false) {
         $randname = $opt['randname'];
         $_REQUEST['short_classname'] = $opt['short_classname'];
         $_REQUEST['f_name'] = $opt['f_name'];
         $_REQUEST['gtype'] = $opt['gtype'];
         $_REQUEST['randname'] = $opt['randname'];

         //End Script for graph display
         //if $randname exists

         $config = PluginMreportingConfig::initConfigParams($opt['f_name'],
                                                            "PluginMreporting".$opt['short_classname']);
         if (!$export) {
            if ($config['graphtype'] == 'GLPI' && $CFG_GLPI['default_graphtype'] == 'svg'
                || $config['graphtype'] == 'SVG') {
               echo "}
                  showGraph$randname();
               </script>";
            }
            echo "</div>";
         }
      }

       if(!$dashboard){
           $request_string = self::getRequestString($_REQUEST);

           if ($export != "odtall") {
               if ($randname !== false && !$export) {

                   $show_graph = PluginMreportingConfig::showGraphConfigValue($opt['f_name'],$opt['class']);
                   self::showGraphDatas($datas, $unit, $labels2, $flip_data,$show_graph);

               }
               if (!$export) {
                  if (isset($_REQUEST['f_name']) && $_REQUEST['f_name'] != "test") {
                     echo "<div class='graph_bottom'>";
                     echo "<span style='float:left'>";
                     echo "<br><br>";
                     self::showNavigation();
                     echo "</span>";
                     echo "<span style='float:right'>";
                     if (plugin_mreporting_haveRight('config', 'w')) {
                        echo "<b>".__("Configuration", 'mreporting')."</b> : ";
                        echo "&nbsp;<a href='config.form.php?name=".$opt['f_name'].
                        "&classname=".$opt['class']."' target='_blank'>";
                        echo "<img src='../pics/config.png' class='title_pics'/></a>";
                     }
                     if ($randname !== false) {

                        echo "<br><br>";
                        echo "<form method='post' action='export.php?$request_string'
                        style='margin: 0; padding: 0' target='_blank' id='export_form'>";
                        echo "<b>".__("Export")."</b> : ";
                        $params = array('myname'   => 'ext',
                           'ajax_page'               => $CFG_GLPI["root_doc"]."/plugins/mreporting/ajax/dropdownExport.php",
                           'class'                   => __CLASS__,
                           'span'                    => 'show_ext',
                           'gtype'                   => $_REQUEST['gtype'],
                           'show_graph'              => $show_graph,
                           'randname'                => $randname);

                        self::dropdownExt($params);

                        echo "<span id='show_ext'>";
                        echo "</span>";
                        Html::Closeform();

                     }
                     echo "</span>";
                  }
                  echo "<div style='clear:both;'></div>";
                  echo "</div>";

                  if (isset($_REQUEST['f_name']) && $_REQUEST['f_name'] != "test") {
                     echo "</div></div>";
                  }
               }

               if ($randname == false) {
                   echo "</div>";
               }
           }
       }

      //destroy specific palette
      unset($_SESSION['mreporting']['colors']);
      unset($_SESSION['mreporting_values']);


   }

   /**
    * Compile datas for unit display
    *
    * @param $datas, ex : array( 'test1' => 15, 'test2' => 25)
    * @param $unit, ex : '%', 'Kg' (optionnal)
    * @if percent, return new datas
    */

   static function compileDatasForUnit($values, $unit = '') {

      $datas = $values;

      if ($unit == '%') {
         //complie news datas with percent values
         $calcul = array();

         $simpledatas = true;
         foreach ($datas as $k=>$v) {
            //multiple array
            if (is_array($v)) {
               $simpledatas = false;
            }
         }
         if (!$simpledatas) {

            $types = array();

            foreach($datas as $k => $v) {

               if (is_array($v)) {
                  foreach($v as $key => $val) {
                     $types[$key][$k] = $val;
                  }
               }
            }
            $datas = $types;
         }

         foreach ($datas as $k=>$v) {
            //multiple array
            if (!$simpledatas) {
               foreach($v as $key => $val) {
                  $total = array_sum($v);
                  if ($total == 0) {
                     $calcul[$k][$key] = Html::formatNumber(0);
                  } else {
                     $calcul[$k][$key]= Html::formatNumber(($val*100)/$total);
                  }
               }
            } else {//simple array
               $total = array_sum($values);
               $calcul[$k]= Html::formatNumber(($v*100)/$total);

            }
         }

         if (!$simpledatas) {
            $datas = array();
            foreach($calcul as $k => $v) {
               if (is_array($v)) {
                  foreach($v as $key => $val) {
                     $datas[$key][$k] = $val;
                  }
               }
            }
         } else {
            $datas = $calcul;
         }

      }

      return $datas;
   }

   /**
    * show Graph datas
    *
    * @param $datas, ex : array( 'test1' => 15, 'test2' => 25)
    * @param $unit, ex : '%', 'Kg' (optionnal)
    * @param $labels2, ex : dates
    * @param $flip_data, flip array if necessary
   */

   static function showGraphDatas ($datas=array(), $unit = '', $labels2=array(), 
                                   $flip_data = false, $show_graph = false) {
      global $CFG_GLPI;


      $simpledatas = false;
      $treedatas = false;

      //simple and tree array
      $depth = self::getArrayDepth($datas);

      if (!$labels2 && $depth < 2) {
         $simpledatas = true;
      }

      if (strtolower($_REQUEST['gtype']) == "sunburst") {
         $treedatas = true;
      }

      if ($flip_data == true) {
         $labels2 = array_flip($labels2);
      }

      $types = array();

      foreach($datas as $k => $v) {
         if (is_array($v)) {
            foreach($v as $key => $val) {
               if (isset($labels2[$key]))
                  $types[$key][$k] = $val;
            }
         }
      }

      if ($flip_data != true) {
         $tmp = $datas;
         $datas = $types;
         $types = $tmp;
      }
      //simple array
      if ($simpledatas) {
         $datas = array(__("Number", 'mreporting') => 0);
      }

      $rand = mt_rand();
      echo "<br><table class='tab_cadre' width='90%'>";
      echo "<tr class='tab_bg_1'><th>";

      echo "<a href=\"javascript:showHideDiv('view_datas$rand','viewimg','".
         $CFG_GLPI["root_doc"]."/pics/deplier_down.png','".
         $CFG_GLPI["root_doc"]."/pics/deplier_up.png');\">";

      if ($show_graph) {
         $img = "deplier_down.png";
      } else {
         $img = "deplier_up.png";
      }
      echo "<img alt='' name='viewimg' src=\"".
         $CFG_GLPI["root_doc"]."/pics/$img\">&nbsp;";

      echo __("data", 'mreporting');
      echo "</a>";
      echo "</th>";
      echo "</tr>";
      echo "</table>";

      if ($show_graph) {
         $visibility = "display:none;";
      } else {
         $visibility = "display:inline;";
      }
      echo "<div align='center' style='".$visibility."' id='view_datas$rand'>";
      echo "<table class='tab_cadre' width='90%'>";

      echo "<tr class='tab_bg_1'>";
      if (!($treedatas)) {
         echo "<th></th>";
      }
      foreach($datas as $label => $cols) {
         if (!empty($labels2)) {
            echo "<th>".$labels2[$label]."</th>";
         } else {
            echo "<th>".$label."</th>";
         }
      }
      echo "</tr>";
      if (($treedatas)) {
         echo "<tr class='tab_bg_1'>";
         self::showGraphTreeDatas($types, $flip_data);
         echo "</tr>";
      } else foreach($types as $label2 => $cols) {
         echo "<tr class='tab_bg_1'>";
         echo "<td>".$label2."</td>";
         if ($simpledatas) { //simple array
            echo "<td class='center'>".$cols." ".$unit."</td>";
         } else if ($treedatas) { //multiple array
            self::showGraphTreeDatas($cols, $flip_data);
         } else { //multiple array
            foreach($cols as $date => $nb) {
               if (!is_array($nb)) {
                  echo "<td class='center'>".$nb." ".$unit."</td>";
               }
            }
         }
         echo "</tr>";
      }

      echo "</table>";
      echo "</div><br>";
   }

   static function showGraphTreeDatas($cols, $flip_data = false) {
      if ($flip_data != true) {
         arsort($cols);
         foreach ($cols as $label => $value) {
            echo "<tr class='tab_bg_1'>";
            echo "<th class='center'>$label</th>";
            echo "<td class='center'>";
            if (is_array($value)) {

               echo "<table class='tab_cadre' width='90%'>";
               self::showGraphTreeDatas($value);
               echo "</table>";
            } else echo $value;
            echo "</td></tr>";
         }
      } else {
         foreach ($cols as $label => $value) {
            echo "<tr class='tab_bg_1'>";
            echo "<th class='center'>$label</th>";
            echo "<td class='center'>";
            if (is_array($value)) {

               echo "<table class='tab_cadre' width='90%'>";
               self::showGraphTreeDatas($value, true);
               echo "</table>";
            } else echo $value;
            echo "</td></tr>";
         }

      }
   }

   /**
    * Launch export of datas
    *
    * @param $opt
   */
   function export($opt) {
      global $LANG;

      switch ($opt['switchto']) {
         default:
         case 'png':
            $graph = new PluginMreportingGraphpng();
            //check the format display charts configured in glpi
            $opt = $this->initParams($opt, true);
            $opt['export']    = 'png';
            $opt['withdata']  = 1;
            break;
         case 'csv':
            $graph = new PluginMreportingGraphcsv();
            $opt['export']    = 'csv';
            $opt['withdata']  = 1;
            break;
         case 'odt':
            $graph = new PluginMreportingGraphpng();
            $opt = $this->initParams($opt, true);
            $opt['export'] = 'odt';
            break;
         case 'odtall':
            $graph = new PluginMreportingGraphpng();
            $opt = $this->initParams($opt, true);
            $opt['export'] = 'odtall';
            break;
      }

      //export all with odt
      if (isset($opt['classname'])) {
         if (isset($opt['check'])) {
            unset($_SESSION['glpi_plugin_mreporting_odtarray']);

            $reports = $this->getAllReports(false, $opt);

            foreach($reports as $classname => $report) {
               foreach($report['functions'] as $func) {
                  foreach ($opt['check'] as $do=>$to) {
                     if ($do == $func['function'].$classname) {
                        //dynamic instanciation of class passed by 'short_classname' GET parameter
                        $config = PluginMreportingConfig::initConfigParams($func['function'], $classname);
                        $class = 'PluginMreporting'.$func['short_classname'];
                        $obj = new $class($config);
                        $randname = $classname.$func['function'];
                        if (isset($opt['date1']) && isset($opt['date2'])) {

                           $s = strtotime($opt['date2'])-strtotime($opt['date1']);

                           // If customExportDates exists in class : we configure the dates
                           if(method_exists($obj, 'customExportDates')){
                              $opt = $obj->customExportDates($opt, $func['function']);
                           }

                           $_REQUEST['date1'.$randname] = $opt['date1'];
                           $_REQUEST['date2'.$randname] = $opt['date2'];
                        }


                        //dynamic call of method passed by 'f_name'
                        //GET parameter with previously instancied class
                        $datas = $obj->$func['function']($config);

                        //show graph (pgrah type determined by
                        //first entry of explode of camelcase of function name
                        $title_func = $LANG['plugin_mreporting'][$func['short_classname']][$func['function']]['title'];

                        $des_func = "";
                        if (isset($LANG['plugin_mreporting'][$func['short_classname']][$func['function']]['desc'])) {
                           $des_func = $LANG['plugin_mreporting'][$func['short_classname']][$func['function']]['desc'];
                        }
                        if (isset($LANG['plugin_mreporting'][$func['short_classname']][$func['function']]['desc'])
                              &&isset($opt['date1'])
                                 && isset($opt['date2'])) {
                           $des_func.= " - ";
                        }

                        if (isset($opt['date1'])
                              && isset($opt['date2'])) {
                           $des_func.= Html::convdate($opt['date1'])." / ".
                              Html::convdate($opt['date2']);
                        }
                        $options = array("short_classname" => $func['short_classname'],
                                    "f_name" => $func['function'],
                                    "class" => $opt['classname'],
                                    "gtype" => $func['gtype'],
                                    "randname" => $randname,
                                    "withdata"   => $opt['withdata']);

                        $show_label = 'always';

                        $params = array("raw_datas"  => $datas,
                                         "title"      => $title_func,
                                         "desc"       => $des_func,
                                         "export"     => $opt['export'],
                                         "opt"        => $options);

                        $graph->{'show'.$func['gtype']}($params);
                     }
                  }
               }
            }
            if (isset($_SESSION['glpi_plugin_mreporting_odtarray']) &&
                  !empty($_SESSION['glpi_plugin_mreporting_odtarray'])) {

               if (PluginMreportingPreference::atLeastOneTemplateExists()) {
                  $template = PluginMreportingPreference::checkPreferenceTemplateValue(Session::getLoginUserID());
                  if ($template) {
                     self::generateOdt($_SESSION['glpi_plugin_mreporting_odtarray']);
                  } else {
                     Html::popHeader(__("General Report - ODT", 'mreporting'), $_SERVER['PHP_SELF']);
                     echo "<div class='center'><br>".__("Please, select a model in your preferences", 'mreporting')."<br><br>";
                     Html::displayBackLink();
                     echo "</div>";
                     Html::popFooter();
                  }
               } else {
                  Html::popHeader(__("General Report - ODT", 'mreporting'), $_SERVER['PHP_SELF']);
                  echo "<div class='center'><br>".__("No model available", 'mreporting')."<br><br>";
                  Html::displayBackLink();
                  echo "</div>";
                  Html::popFooter();
               }
            }
         } else { //no selected data
            Html::popHeader(__("General Report - ODT", 'mreporting'), $_SERVER['PHP_SELF']);
            echo "<div class='center'><br>".__("No graphic selected", 'mreporting')."<br><br>";
            Html::displayBackLink();
            echo "</div>";
            Html::popFooter();
         }

      } else {
         $config = PluginMreportingConfig::initConfigParams($opt['f_name'],
         "PluginMreporting".$opt['short_classname']);

         // get periods selected
         PluginMreportingCommon::getSelectorValuesByUser();

         //dynamic instanciation of class passed by 'short_classname' GET parameter
         $classname = 'PluginMreporting'.$opt['short_classname'];
         $obj = new $classname($config);

         //dynamic call of method passed by 'f_name' GET parameter with previously instancied class
         $datas = $obj->$opt['f_name']($config);

         //show graph (pgrah type determined by first entry of explode of camelcase of function name
         $title_func = $LANG['plugin_mreporting'][$opt['short_classname']][$opt['f_name']]['title'];
         $des_func = "";
         if (isset($LANG['plugin_mreporting'][$opt['short_classname']][$opt['f_name']]['desc'])) {
            $des_func = $LANG['plugin_mreporting'][$opt['short_classname']][$opt['f_name']]['desc'];
         }
         if (isset($LANG['plugin_mreporting'][$opt['short_classname']][$opt['f_name']]['desc'])
               && isset($_REQUEST['date1'.$opt['randname']])
               && isset($_REQUEST['date2'.$opt['randname']])) {
            $des_func.= " - ";
         }
         if (isset($_REQUEST['date1'.$opt['randname']])
               && isset($_REQUEST['date2'.$opt['randname']])) {
            $des_func.= Html::convdate($_REQUEST['date1'.$opt['randname']]).
                        " / ".Html::convdate($_REQUEST['date2'.$opt['randname']]);
         }

         $show_label = 'always';

         $opt['class'] = $classname;
         $params = array("raw_datas"  => $datas,
                          "title"      => $title_func,
                          "desc"       => $des_func,
                          "export"     => $opt['export'],
                          "opt"        => $opt);

         $graph->{'show'.$opt['gtype']}($params);
      }
   }

   static function generateOdt($params) {
      global $LANG;

      $config = array('PATH_TO_TMP' => GLPI_DOC_DIR . '/_tmp');

      if (PluginMreportingPreference::atLeastOneTemplateExists()) {

         $template = PluginMreportingPreference::checkPreferenceTemplateValue(
            Session::getLoginUserID());


         $withdatas = $params[0]["withdata"];

         if ($withdatas == 0) {
            $odf    = new odf("../templates/withoutdata.odt", $config);
         } else {
            $odf    = new odf("../templates/$template", $config);
         }
         $titre = '';
         $short_classname = str_replace('PluginMreporting', '', $params[0]['class']);
         if (isset($LANG['plugin_mreporting'][$short_classname]['title'])) {
            $titre = $LANG['plugin_mreporting'][$short_classname]['title'];
         }

         $odf->setVars('titre', $titre, true, 'UTF-8');

         $newpage = $odf->setSegment('newpage');

         foreach ($params as $result => $page) {
            // Default values of parameters
            $title       = "";
            $f_name      = "";
            $raw_datas   = array();

            foreach ($page as $key => $val) {
               $$key=$val;
            }

            $datas = $raw_datas['datas'];

            $labels2 = array();
            if (isset($raw_datas['labels2'])) {
               $labels2 = $raw_datas['labels2'];
            }

            $configs = PluginMreportingConfig::initConfigParams($f_name, $class);

            foreach ($configs as $k => $v) {
               $$k=$v;
            }

            if ($unit == '%') {

               $datas = self::compileDatasForUnit($datas, $unit);
            }

            $newpage->setVars('message', $title, true, 'UTF-8');

            $path = GLPI_PLUGIN_DOC_DIR."/mreporting/".$f_name.".png";

            if ($show_graph) {
               $newpage->setImage('image', $path);
            } else {
               $newpage->setVars('image', "", true, 'UTF-8');
            }

            if ($withdatas > 0) {
               $simpledatas = false;

               //simple array
               if (!$labels2) {
                  $labels2 = array();
                  $simpledatas = true;
               }

               if ($flip_data == true) {
                  $labels2 = array_flip($labels2);
               }

               $types = array();

               foreach($datas as $k => $v) {
                  if (is_array($v)) {
                     foreach($v as $key => $val) {
                        if (isset($labels2[$key]))
                           $types[$key][$k] = $val;
                     }
                  } else {
                     $types[$k] = $v;
                  }
               }

               if ($flip_data != true) {
                  $tmp = $datas;
                  $datas = $types;
                  $types = $tmp;
               }
               //simple array
               if ($simpledatas) {

                  $label = __("Number", 'mreporting');

                  if ($template == "word.odt") {
                     $newpage->data0->label_0(utf8_decode($label));
                     $newpage->data0->merge();
                  } else {
                     $newpage->csvdata->setVars('TitreCategorie', $label, true, 'UTF-8');
                  }
                  if ($template == "word.odt") {
                     foreach($types as $label2 => $cols) {
                        $newpage->csvdata->label1->label_1(utf8_decode($label2));
                        $newpage->csvdata->label1->merge();

                        if (!empty($unit)) {
                           $cols = $cols." ".$unit;
                        }
                        $newpage->csvdata->data1->data_1($cols);
                        $newpage->csvdata->merge();
                     }
                  } else {
                     foreach($types as $label2 => $cols) {
                        if (!empty($unit)) {
                           $cols = $cols." ".$unit;
                        }

                        $newpage->csvdata->csvdata2->label_1(utf8_decode($label2));
                        $newpage->csvdata->csvdata2->data_1($cols);
                        $newpage->csvdata->csvdata2->merge();
                     }
                     $newpage->csvdata->merge();
                  }

               } else {

                  if ($template == "word.odt") {
                     foreach($datas as $label => $val) {
                        $newpage->data0->label_0(utf8_decode($label));
                        $newpage->data0->merge();
                     }

                     foreach($types as $label2 => $cols) {
                        $newpage->csvdata->label1->label_1(utf8_decode($label2));
                        $newpage->csvdata->label1->merge();

                        foreach($cols as $date => $nb) {
                           if (!empty($unit)) {
                              $nb = $nb." ".$unit;
                           }
                           if (!is_array($nb)) $newpage->csvdata->data1->data_1(utf8_decode($nb));
                           $newpage->csvdata->data1->merge();
                        }

                        $newpage->csvdata->merge();
                     }
                  } else {
                     foreach($types as $label2 => $cols) {
                        foreach($cols as $label1 => $nb) {
                           if (!empty($unit)) {
                              $nb = $nb." ".$unit;
                           }
                           $newpage->csvdata->setVars('TitreCategorie', $label2, true, 'UTF-8');
                           $newpage->csvdata->csvdata2->setVars(
                              'label_1', utf8_decode($label1), true, 'UTF-8');
                           if (!is_array($nb)) {
                              $newpage->csvdata->csvdata2->setVars(
                                 'data_1', utf8_decode($nb), true, 'UTF-8');
                           }
                           $newpage->csvdata->csvdata2->merge();
                        }

                        $newpage->csvdata->merge();
                     }
                  }
               }
            }
            $newpage->merge();

         }
         $odf->mergeSegment($newpage);
         // We export the file
         $odf->exportAsAttachedFile();
         unset($_SESSION['glpi_plugin_mreporting_odtarray']);
      }
   }

   // === SELECTOR FUNCTIONS ====

   static function selectorForMultipleGroups($field, $condition = '', $label = '') {
      global $DB;

      $values = array();
      if (isset($_SESSION['mreporting_values'][$field])) {
         $values = $_SESSION['mreporting_values'][$field];
      }

      $datas = array();
      foreach (getAllDatasFromTable('glpi_groups', $condition) as $data) {
         $datas[$data['id']] = $data['completename'];
      }

      $param = array();
      $param['multiple']= true;
      $param['display'] = true;
      $param['size']    = count($values);
      $param['values']  = $values;
      
      echo "<br /><b>".$label." : </b><br />";
      Dropdown::showFromArray($field, $datas, $param);

   }

   static function selectorForSingleGroup($field, $conditon = '', $label = '') {
      echo "<br /><b>".$label." : </b><br />";
      if (isset($_SESSION['mreporting_values'][$field])) {
         $value = isset($_SESSION['mreporting_values'][$field]);
      } else {
         $value = 0;
      }
      Dropdown::show("Group",array('comments'  => false,
                                   'name'      => $field,
                                   'value'     => $value,
                                   'condition' => $condition)
                    );
   }


   static function selectorGrouprequest() {
      self::selectorForSingleGroup('groups_request_id', 'is_requester = 1', __("Requester group"));
   }

   static function selectorGroupassign() {
      self::selectorForSingleGroup('groups_assign_id', 'is_assign = 1',
                                   __("Group in charge of the ticket"));
   }

   static function selectorMultipleGrouprequest() {
      self::selectorForMultipleGroups('groups_request_id', "`is_requester`='1'", __("Requester group"));
   }

   static function selectorMultipleGroupassign() {
      self::selectorForMultipleGroups('groups_assign_id', "`is_assign`='1'",
                                      __("Group in charge of the ticket"));
   }

   static function selectorUserassign() {
      echo "<br /><b>".__("Technician in charge of the ticket")." : </b><br />";
      $options = array('name'        => 'users_assign_id',
                       'entity'      => $_SESSION['glpiactive_entity'],
                       'right'       => 'own_ticket',
                       'value'       => isset($_SESSION['mreporting_values']['users_assign_id']) ? $_SESSION['mreporting_values']['users_assign_id'] : 0,
                       'ldap_import' => false,
                       'comments'    => false);
      User::dropdown($options);
   }
   /**
    * Show the selector for tickets status (new, open, solved, closed...)
    */
   static function selectorAllSlasWithTicket()
   {
      global $LANG, $DB;

      $query = "SELECT DISTINCT
         s.id,
         s.name
      FROM glpi_slas s
      INNER JOIN glpi_tickets t ON s.id = t.slas_id
      WHERE t.status IN (" . implode(
            ',',
            array_merge(Ticket::getSolvedStatusArray(), Ticket::getClosedStatusArray())
         ) . ")
      AND t.is_deleted = '0'
      ORDER BY s.name ASC";

      $selected_values = array();
      if (isset($_SESSION['mreporting_values']['slas'])) {
         $selected_values = $_SESSION['mreporting_values']['slas'];
      }

      echo "<b>" . $LANG['plugin_mreporting']['selector']["slas"] . " : </b><br />";
      echo "<select name='slas[]' multiple class='chzn-select' data-placeholder='-----                 '>";
      $result = $DB->query($query);
      while ($data = $DB->fetch_assoc($result)) {
         $selected = "";
         if (in_array($data['id'], $selected_values)) {
            $selected = "selected ";
         }
         echo "<option value='".$data['id']."' $selected>".$data['name']."</option>";
      }

   }

   static function selectorPeriod($period = "day") {
      global $LANG;
      $elements = array(
         'day'    => _n("Day", "Days", 1),
         'week'   => __("Week"),
         'month'  => _n("Month", "Months", 1),
         'year'   => __("By year"),
      );

      echo '<b>'.$LANG['plugin_mreporting']['Helpdeskplus']['period'].' : </b><br />';
      Dropdown::showFromArray("period", $elements,
                              array('value' => isset($_SESSION['mreporting_values']['period'])
                                 ? $_SESSION['mreporting_values']['period'] : 'month'));
   }

   static function selectorType() {
      echo "<br /><b>"._n("Type", "Types", 1) ." : </b><br />";
      Ticket::dropdownType('type', 
                           array('value' => isset($_SESSION['mreporting_values']['type']) 
                              ? $_SESSION['mreporting_values']['type'] : Ticket::INCIDENT_TYPE));

   }

   static function selectorCategory($type = true) {
      global $CFG_GLPI;

      echo "<br /><b>"._n("Category of ticket", "Categories of tickets", 2) ." : </b><br />";
      if ($type) {
         $rand = Ticket::dropdownType('type', 
                                      array('value' => isset($_SESSION['mreporting_values']['type']) 
                                                       ? $_SESSION['mreporting_values']['type'] 
                                                       : Ticket::INCIDENT_TYPE, 
                                             'toadd' => array(-1 => __('All'))));
         $params = array('type'            => '__VALUE__',
                         'currenttype'     => Ticket::INCIDENT_TYPE,
                         'entity_restrict' => -1,
                         'value'           => isset($_SESSION['mreporting_values']['itilcategories_id']) 
                                              ? $_SESSION['mreporting_values']['itilcategories_id'] 
                                              : 0);
         echo "<span id='show_category_by_type'>";
         $params['condition'] = "`is_incident`='1'";
      }
      $params['comments'] = false;
      ITILCategory::dropdown($params);
      if ($type) {
         echo "</span>";

         Ajax::updateItemOnSelectEvent("dropdown_type$rand", "show_category_by_type",
                                       $CFG_GLPI["root_doc"]."/ajax/dropdownTicketCategories.php",
                                       $params);
      }
   }

   static function selectorLimit() {
      echo "<b>".__("Maximal count")." :</b><br />";
      Dropdown::showListLimit(); // glpilist_limit
   }


   static function selectorAllstates() {
      echo "<br><b>"._n('Status', 'Statuses', 2)." : </b><br />";
      $default = array(CommonITILObject::INCOMING,
                       CommonITILObject::ASSIGNED,
                       CommonITILObject::PLANNED,
                       CommonITILObject::WAITING);

      $i = 1;
      foreach(Ticket::getAllStatusArray() as $value => $name) {
         echo '<label>';
         echo '<input type="hidden" name="status_'.$value.'" value="0" /> ';
         echo '<input type="checkbox" name="status_'.$value.'" value="1"';
         if((isset($_SESSION['mreporting_values']['status_'.$value])
            && ($_SESSION['mreporting_values']['status_'.$value] == '1'))
               || (!isset($_SESSION['mreporting_values']['status_'.$value])
                  && in_array($value, $default))) {
            echo ' checked="checked"';
         }
         echo ' /> ';
         echo $name;
         echo '</label>';
         if ($i%3 == 0) echo "<br />";
         $i++;
      }
   }

   static function selectorDateinterval() {
      $randname = 'PluginMreporting'.$_REQUEST['short_classname'].$_REQUEST['f_name'];

      if (!isset($_SESSION['mreporting_values']['date1'.$randname]))
         $_SESSION['mreporting_values']['date1'.$randname] = strftime("%Y-%m-%d", time() - (365 * 24 * 60 * 60));
      if (!isset($_SESSION['mreporting_values']['date2'.$randname]))
         $_SESSION['mreporting_values']['date2'.$randname] = strftime("%Y-%m-%d");

      $date1    = $_SESSION['mreporting_values']["date1".$randname];
      $date2    = $_SESSION['mreporting_values']["date2".$randname];
      echo "<b>".__("Start date")."</b><br />";
      Html::showDateFormItem("date1".$randname, $date1, false);
      echo "</td><td>";
      echo "<b>".__("End date")."</b><br />";
      Html::showDateFormItem("date2".$randname, $date2, false);   
   } 
   
   static function canAccessAtLeastOneReport($profiles_id) {
      return countElementsInTable("glpi_plugin_mreporting_profiles", 
                                  "`profiles_id`='$profiles_id' AND `right`= ".READ);
   }

   static function showNavigation() {
      echo "<div class='center'>";
      echo "<a href='central.php'>".__("Back")."</a>";
      echo "</div>";
   }


   /**
    * Transform a request var into a get string
    * @param  array $var the request string ($_REQUEST, $_POST, $_GET)
    * @return string the imploded array. Format : $key=$value&$key2=$value2...
    */
   static function getRequestString($var) {
      unset($var['submit']);

      return http_build_query($var);
   }


   /**
    * Show a date selector
    * @param  datetime $date1    date of start
    * @param  datetime $date2    date of ending
    * @param  string $randname random string (to prevent conflict in js selection)
    * @return nothing
    */
   static function showSelector($date1, $date2, $randname) {
      global $CFG_GLPI;

      $request_string = self::getRequestString($_GET);
      if (!isset($_REQUEST['f_name'])) {
         $has_selector = false;
      } else {
         $has_selector   = (isset($_SESSION['mreporting_selector'][$_REQUEST['f_name']]));
      }
      echo "<div class='center'><form method='POST' action='?$request_string' name='form'"
         ." id='mreporting_date_selector'>\n";
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr class='tab_bg_1'>";

      if ($has_selector) {
         self::getReportSelectors();
      }

      echo "<td colspan='2' class='center'>";
      if ($has_selector) {
         echo "<input type='submit' class='submit' name='submit'
                value=\"". _sx('button', 'Post') ."\">";
      }
      $_SERVER['REQUEST_URI'] .= "&date1".$randname."=".$date1;
      $_SERVER['REQUEST_URI'] .= "&date2".$randname."=".$date2;
      Bookmark::showSaveButton(Bookmark::URI, __CLASS__);

      //If there's no selector for the report, there's no need for a reset button !
      if ($has_selector) {
         echo "<a href='?$request_string&reset=reset' >";
         echo "&nbsp;&nbsp;<img title=\"".__s('Blank')."\" alt=\"".__s('Blank')."\" src='".
               $CFG_GLPI["root_doc"]."/pics/reset.png' class='calendrier'></a>";
      }
      echo "</td>\n";
      unset($_SESSION['mreporting_selector']);

      echo "</tr>";
      echo "</table>";
      Html::closeForm();
      echo "</div>\n";

      if(isset($_SERVER['HTTP_USER_AGENT']) 
         && !preg_match('/(?i)msie [1-8]/',$_SERVER['HTTP_USER_AGENT'])) {
         echo "<script type='text/javascript'>
         var elements = document.querySelectorAll('.chzn-select');
         for (var i = 0; i < elements.length; i++) {
            new Chosen(elements[i], {});
         }
         </script>";
      }
   }

   /**
    * Parse and include selectors functions
    */
   static function getReportSelectors($export = false) {
       ob_start();
      self::addToSelector();
      $graphname = $_REQUEST['f_name'];
      if(!isset($_SESSION['mreporting_selector'][$graphname])
         || empty($_SESSION['mreporting_selector'][$graphname])) return;

      $classname = 'PluginMreporting'.$_REQUEST['short_classname'];
      if(!class_exists($classname)) return;

      $i = 1;
      foreach ($_SESSION['mreporting_selector'][$graphname] as $selector) {
         if($i%4 == 0) echo '</tr><tr class="tab_bg_1">';
         $selector = 'selector'.ucfirst($selector);
         if(method_exists('PluginMreportingCommon', $selector)) {
            $classselector = 'PluginMreportingCommon';
         } elseif (method_exists($classname, $selector)) {
            $classselector = $classname;
         } else {
            continue;
         }

         $i++;
         echo '<td>';
         $classselector::$selector();
         echo '</td>';
      }
      while($i%4 != 0) {
         $i++;
         echo '<td>&nbsp;</td>';
      }

       $res = ob_get_clean();

       if($export)return $res;
       else echo $res;
   }

   static function saveSelectors($graphname, $config = array()) {
      $remove = array('short_classname', 'f_name', 'gtype', 'submit');
      $values = array();
      $pref   = new PluginMreportingPreference();


      foreach ($_REQUEST as $key => $value) {
         if (!preg_match("/^_/", $key) && !in_array($key, $remove) ) {
            $values[$key] = $value;
         }
         if (empty($value)) {
            unset($_REQUEST[$key]);
         }
      }

      //clean unmodified date
      if (isset($_REQUEST['date1'.$config['randname']])
         && $_REQUEST['date1'.$config['randname']]
            == $_SESSION['mreporting_values']['date1'.$config['randname']]) {
         unset($_REQUEST['date1'.$config['randname']]);
      }
      if (isset($_REQUEST['date2'.$config['randname']])
         && $_REQUEST['date2'.$config['randname']]
            == $_SESSION['mreporting_values']['date2'.$config['randname']]) {
         unset($_REQUEST['date2'.$config['randname']]);
      }

      if (!empty($values)) {
          $id               = $pref->addDefaultPreference(Session::getLoginUserID());
          $tmp['id']        = $id;
          $pref->getFromDB($id);
          if (!is_null($pref->fields['selectors'])) {
            $selectors = $pref->fields['selectors'];
            $sel = json_decode(stripslashes($selectors), true);
            $sel[$graphname] = $values;
          } else {
             $sel = $values;
          }
          $tmp['selectors'] = addslashes(json_encode($sel));
          $pref->update($tmp);
      }
      $_SESSION['mreporting_values'] = $values;
   }

   static function getSelectorValuesByUser() {
      global $DB;

      $myvalues  = (isset($_SESSION['mreporting_values'])?$_SESSION['mreporting_values']:array());
      $selectors = PluginMreportingPreference::checkPreferenceValue('selectors', Session::getLoginUserID());
      if ($selectors) {
         $values = json_decode(stripslashes($selectors), true);
         if (isset($values[$_REQUEST['f_name']])) {
            foreach ($values[$_REQUEST['f_name']] as $key => $value) {
               $myvalues[$key] = $value;
            }
         }
      }
      $_SESSION['mreporting_values'] = $myvalues;
   }

   static function addToSelector() {
      foreach ($_REQUEST as $key => $value) {
         if (!isset($_SESSION['mreporting_values'][$key])) {
             $_SESSION['mreporting_values'][$key] = $value;
         }
      }
   }

   static function resetSelectorsForReport($report) {
      global $DB;

      $users_id  = Session::getLoginUserID();
      $selectors = PluginMreportingPreference::checkPreferenceValue('selectors', $users_id);
      if ($selectors) {
         $values = json_decode(stripslashes($selectors), true);
         if (isset($values[$report])) {
            unset($values[$report]);
         }
         $sel = addslashes(json_encode($values));
         $query = "UPDATE `glpi_plugin_mreporting_preferences`
                   SET `selectors`='$sel'
                   WHERE `users_id`='$users_id'";
         $DB->query($query);
      }
   }

   /**
    * Generate a SQL date test with $_REQUEST date fields
    * @param  string  $field     the sql table field to compare
    * @param  integer $delay     if $_REQUET date fields not provided,
    *                            generate them from $delay (in days)
    * @param  string $randname   random string (to prevent conflict in js selection)
    * @return string             The sql test to insert in your query
    */
   static function getSQLDate($field = "`glpi_tickets`.`date`", $delay=365, $randname) {

      if (empty($_SESSION['mreporting_values']['date1'.$randname]))
         $_SESSION['mreporting_values']['date1'.$randname] = strftime("%Y-%m-%d", time() - ($delay * 24 * 60 * 60));
      if (empty($_SESSION['mreporting_values']['date2'.$randname]))
         $_SESSION['mreporting_values']['date2'.$randname] = strftime("%Y-%m-%d");

      $date_array1=explode("-",$_SESSION['mreporting_values']['date1'.$randname]);
      $time1=mktime(0,0,0,$date_array1[1],$date_array1[2],$date_array1[0]);

      $date_array2=explode("-",$_SESSION['mreporting_values']['date2'.$randname]);
      $time2=mktime(0,0,0,$date_array2[1],$date_array2[2],$date_array2[0]);

      //if data inverted, reverse it
      if ($time1 > $time2) {
         list($time1, $time2) = array($time2, $time1);
         list($_SESSION['mreporting_values']['date1'.$randname],
            $_SESSION['mreporting_values']['date2'.$randname]) = array(
            $_SESSION['mreporting_values']['date2'.$randname],
            $_SESSION['mreporting_values']['date1'.$randname]
         );
      }

      $begin=date("Y-m-d H:i:s",$time1);
      $end=date("Y-m-d H:i:s",$time2);

      return "($field >= '$begin' AND $field <= ADDDATE('$end' , INTERVAL 1 DAY) )";
   }


   /**
    * Get the max value of a multidimensionnal array
    * @param  array() $array the array to compute
    * @return number the sum
    */
   static function getArrayMaxValue($array) {
      $max = 0;

      if (!is_array($array)) return $array;

      foreach ($array as $value) {
         if (is_array($value)) {
            $sub_max = self::getArrayMaxValue($value);
            if ($sub_max > $max) $max = $sub_max;
         } else{
            if ($value > $max) $max = $value;
         }
      }

      return $max;
   }


   /**
    * Computes the sum of a multidimensionnal array
    * @param  array() $array the array where to seek
    * @return number the sum
    */
   static function getArraySum($array ) {
      $sum = 0;

      if (!is_array($array)) return $array;
      foreach ($array as $value) {
         if (is_array($value)) {
            $sum+= self::getArraySum($value);
         } else{
            $sum+= $value;
         }
      }

      return $sum;
   }


   /**
    * Get the depth of a multidimensionnal array
    * @param  array() $array the array where to seek
    * @return number the sum
    */
   static function getArrayDepth($array) {
      $max_depth = 1;

      foreach ($array as $value) {
         if (is_array($value)) {
            $depth = self::getArrayDepth($value) + 1;

            if ($depth > $max_depth) {
               $max_depth = $depth;
            }
         }
      }

      return $max_depth;
   }


   /**
    * Transform a flat array to a tree array
    * @param  array $flat_array the flat array. Format : array('id', 'parent', 'name', 'count')
    * @return array the tree array. Format : array(name => array(name2 => array(count), ...)
    */
   static function buildTree($flat_array) {
      $raw_tree = self::mapTree($flat_array);
      $tree = self::cleanTree($raw_tree);
      return $tree;
   }


   /**
    * Transform a flat array to a tree array (without keys changes)
    * @param  array $flat_array the flat array. Format : array('id', 'parent', 'name', 'count')
    * @return array the tree array. Format : array(orginal_keys, children => array(...)
    */
   static function mapTree(array &$elements, $parentId = 0) {
      $branch = array();

      foreach ($elements as $element) {
         if (isset($element['parent']) && $element['parent'] == $parentId) {
            $children = self::mapTree($elements, $element['id']);
            if ($children) {
                $element['children'] = $children;
            }
            $branch[$element['id']] = $element;
         }
      }
      return $branch;
   }


   /**
    * Transform a tree array to a tree array (with clean keyss)
    * @param  array $flat_array the tree array.
    *               Format : array('id', 'parent', 'name', 'count', children => array(...)
    * @return array the tree array.
    *               Format : array(name => array(name2 => array(count), ...)
    */
   static function cleanTree($raw_tree) {
      $tree = array();

      foreach ($raw_tree as $id => $node) {
         if (isset($node['children'])) {
            $sub = self::cleanTree($node['children']);

            if ($node['count'] > 0) {
               $current = array($node['name'] => intval($node['count']));
               $tree[$node['name']] = array_merge($current, $sub);
            } else {
               $tree[$node['name']] = $sub;
            }
         } else {
            $tree[$node['name']] = intval($node['count']);
         }
      }

      return $tree;
   }

   static function getIcon($report_name) {
      $extract    = preg_split('/(?<=\\w)(?=[A-Z])/', $report_name);
      $chart_type = strtolower($extract[1]);

      //see font-awesome : http://fortawesome.github.io/Font-Awesome/cheatsheet/
      $icons = array(
         'pie'       => "&#xf200",
         'hbar'      => "&#xf036;",
         'hgbar'     => "&#xf036;",
         'line'      => "&#xf201;",
         'gline'     => "&#xf201;",
         'area'      => "&#xf1fe;",
         'garea'     => "&#xf1fe;",
         'vstackbar' => "&#xf080;",
         'sunburst'  => "&#xf185;",
      );

      return $icons[strtolower($chart_type)];
   }
}
