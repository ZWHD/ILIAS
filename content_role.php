<?php
include_once "include/ilias_header.inc";


// Template generieren
$tplContent = new Template("content_role.html",true,true);

$tplContent->setVariable("OBJ_SELF","content_role.php?obj_id=$obj_id&parent=$parent");
$tplContent->setVariable("OBJ_ID",$obj_id);
$tplContent->setVariable("TPOS",$parent);

// display path
$tree = new Tree($obj_id,1,1);
$path = $tree->showPath($tree->getPathFull(),"content.php");
$tplContent->setVariable("TREEPATH",$path);
$tplContent->setVariable("MESSAGE","<h5>Click on the name of a role to edit the template of that role</h5>");
$tplContent->setVariable("TYPE","role");

// BEGIN ROW
$tplContent->setCurrentBlock("row",true);
$rbacadmin = new RbacAdminH($ilias->db);

// determine sort direction
if(!$_GET["direction"] || $_GET["direction"] == 'ASC')
{
	$tplContent->setVariable("DIR",'DESC');
}
if($_GET["direction"] == 'DESC')
{
	$tplContent->setVariable("DIR",'ASC');
}

// set sort column
if (empty($_GET["order"]))
{
	$_GET["order"] = "title";
}

if($rbacsystem->checkAccess('read',$_GET["obj_id"],$_GET["parent"]))
{
	if($role_list = $rbacadmin->getRoleAndTemplateListByObject($obj_id,$_GET["order"],$_GET["direction"]))
	{
		foreach($role_list as $key => $val)
		{
			// color changing
			if ($key % 2)
			{
				$css_row = "row_high";	
			}
			else
			{
				$css_row = "row_low";
			}

			$node = "[<a href=\"content.php?obj_id=".$val["id"]."&parent=".$val["parent"]."\">".$val["title"]."</a>]";
			$tplContent->setVariable("LINK_TARGET","object.php?obj_id=".$val["obj_id"]."&parent=$obj_id&cmd=perm&show=rolf");
			$tplContent->setVariable("OBJ_TITLE",$val["title"]);
			$tplContent->setVariable("OBJ_LAST_UPDATE",$val["last_update"]);

			// determine image (role object or role template?)
			$image = $val["type"] == 'rolt' ? 'icon_rtpl_b.gif' : 'icon_role_b.gif';

			$tplContent->setVariable("IMG_TYPE",$image);
			$tplContent->setVariable("ALT_IMG_TYPE",$val["type"]);

			$tplContent->setVariable("CSS_ROW",$css_row);
			$tplContent->setVariable("OBJ",$val["obj_id"]);
			$tplContent->parseCurrentBlock("row");
		}
	}
}
else
{
	$ilias->raiseError("No permission to read role folder",$ilias->error_class->MESSAGE);
}
if($_GET["message"])
{
	$tplContent->setCurrentBlock("sys_message");
	$tplContent->setVariable("ERROR_MESSAGE",$_GET["message"]);
	$tplContent->parseCurrentBlock();
}
include_once "include/ilias_footer.inc";
?>