<?php
/*
 * @version $Id: HEADER 15930 2011-10-30 15:47:55Z tsmr $
 -------------------------------------------------------------------------
 consumables plugin for GLPI
 Copyright (C) 2009-2016 by the consumables Development Team.

 https://github.com/InfotelGLPI/consumables
 -------------------------------------------------------------------------

 LICENSE

 This file is part of consumables.

 consumables is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 consumables is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with consumables. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

include('../../../inc/includes.php');

header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

Session::checkLoginUser();

// Make a select box
if (isset($_POST["idtable"]) && !empty($_POST["idtable"]) && class_exists($_POST["idtable"])) {
   global $DB;
   
   $itemtype = $_POST["idtable"];
   $rand = mt_rand();
   $name = $_POST["name"];
   
   // html generate for dropdown
   if ($itemtype == 'User') {
      $query = "SELECT u.id, u.firstname, u.realname, u.name as username
                FROM glpi_users u
                WHERE u.is_deleted = 0 
                AND u.is_active = 1";
      
      if (isset($_POST['entity_restrict'])) {
         $entities = is_array($_POST['entity_restrict']) ? $_POST['entity_restrict'] : [$_POST['entity_restrict']];
      }
      
      $query .= " ORDER BY u.realname, u.firstname";
      
      $result = $DB->query($query);
      
      echo "<select name='{$name}' id='dropdown_{$name}{$rand}' class='form-select'>";
      echo "<option value='0'>-----</option>";
      
      while ($row = $DB->fetchAssoc($result)) {
         $displayName = trim($row['firstname'] . ' ' . $row['realname']);
         if (empty($displayName)) {
            $displayName = $row['username'];
         }
         if (empty($displayName)) {
            $displayName = 'User #' . $row['id'];
         }
         echo "<option value='{$row['id']}'>" . htmlspecialchars($displayName) . "</option>";
      }
      
      echo "</select>";
      
   } elseif ($itemtype == 'Group') {
      $user_groups = [];
      $groups = Group_User::getUserGroups(Session::getLoginUserID());
      foreach ($groups as $group) {
         $user_groups[] = intval($group['id']);
      }
      
      if (!empty($user_groups)) {
         $query = "SELECT g.id, g.name
                   FROM glpi_groups g
                   WHERE g.is_deleted = 0
                   AND g.id IN (" . implode(',', $user_groups) . ")";
         
         if (isset($_POST['entity_restrict'])) {
            $entities = is_array($_POST['entity_restrict']) ? $_POST['entity_restrict'] : [$_POST['entity_restrict']];
            $query .= " AND g.entities_id IN (" . implode(',', array_map('intval', $entities)) . ")";
         }
         
         $query .= " ORDER BY g.name";
         
         $result = $DB->query($query);
         
         echo "<select name='{$name}' id='dropdown_{$name}{$rand}' class='form-select'>";
         echo "<option value='0'>-----</option>";
         
         while ($row = $DB->fetchAssoc($result)) {
            echo "<option value='{$row['id']}'>" . htmlspecialchars($row['name']) . "</option>";
         }
         
         echo "</select>";
      } else {
         echo "<select name='{$name}' id='dropdown_{$name}{$rand}' class='form-select'>";
         echo "<option value='0'>-----</option>";
         echo "</select>";
         echo "<div class='text-muted'><small>" . __('You do not belong to any group') . "</small></div>";
      }
      
   } else {
      $dbu = new DbUtils();
      $table = $dbu->getTableForItemType($itemtype);

      $link = "getDropdownValue.php";

      if ($itemtype == 'User') {
         $link = "getDropdownUsers.php";
      }

      $field_id = Html::cleanId("dropdown_" . $name . $rand);

      $p = [
         'value'               => 0,
         'valuename'           => Dropdown::EMPTY_VALUE,
         'itemtype'            => $itemtype,
         'display_emptychoice' => true,
         'displaywith'         => ['otherserial', 'serial'],
         '_idor_token'         => Session::getNewIDORToken($itemtype),
      ];
      
      if (isset($_POST['value'])) {
         $p['value'] = $_POST['value'];
      }
      if (isset($_POST['entity_restrict'])) {
         $p['entity_restrict'] = $_POST['entity_restrict'];
      }
      if (isset($_POST['condition'])) {
         $p['condition'] = $_POST['condition'];
      }

      echo Html::jsAjaxDropdown($name, $field_id,
                                $CFG_GLPI['root_doc'] . "/ajax/" . $link,
                                $p);

      if (!empty($_POST['showItemSpecificity'])) {
         $params = ['items_id' => '__VALUE__',
                    'itemtype' => $itemtype];
         if (isset($_POST['entity_restrict'])) {
            $params['entity_restrict'] = $_POST['entity_restrict'];
         }

         Ajax::updateItemOnSelectEvent($field_id, "showItemSpecificity_" . $name . "$rand",
                                       $_POST['showItemSpecificity'], $params);

         echo "<br><span id='showItemSpecificity_" . $name . "$rand'>&nbsp;</span>\n";
      }
   }
}