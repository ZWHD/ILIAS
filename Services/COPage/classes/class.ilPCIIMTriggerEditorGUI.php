<?php

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once("./Services/COPage/classes/class.ilPCImageMapEditorGUI.php");

/**
* User interface class for page content map editor
*
* @author Alex Killing <alex.killing@gmx.de>
* @version $Id$
*
* @ilCtrl_Calls ilPCIIMTriggerEditorGUI: ilInternalLinkGUI
*
* @ingroup ServicesCOPage
*/
class ilPCIIMTriggerEditorGUI extends ilPCImageMapEditorGUI
{
	/**
	* Constructor
	*/
	function __construct($a_content_obj, $a_page)
	{
		parent::__construct($a_content_obj, $a_page);
	}
	
	/**
	 * Get parent node name
	 *
	 * @return string name of parent node
	 */
	function getParentNodeName()
	{
		return "InteractiveImage";
	}

	/**
	 * Get editor title
	 *
	 * @return string editor title
	 */
	function getEditorTitle()
	{
		global $lng;
		
		return $lng->txt("cont_pc_iim");
	}

	/**
	 * Get trigger table
	 */
	function getImageMapTableHTML()
	{
		include_once("./Services/COPage/classes/class.ilPCIIMTriggerTableGUI.php");
		$image_map_table = new ilPCIIMTriggerTableGUI($this, "editMapAreas", $this->content_obj,
			$this->getParentNodeName());
		return $image_map_table->getHTML();
	}

	/**
	 * Get toolbar
	 *
	 * @return object toolbar
	 */
	function getToolbar()
	{
		global $ilCtrl, $lng;
		
		// toolbar
		$tb = new ilToolbarGUI();
		$tb->setFormAction($ilCtrl->getFormAction($this));
		include_once("./Services/Form/classes/class.ilSelectInputGUI.php");
		$options = array(
			"Rect" => $lng->txt("cont_Rect"),
			"Circle" => $lng->txt("cont_Circle"),
			"Poly" => $lng->txt("cont_Poly"),
			"Marker" => $lng->txt("cont_marker")
			);
		$si = new ilSelectInputGUI($lng->txt("cont_trigger_area"), "shape");
		$si->setOptions($options);
		$tb->addInputItem($si, true);
		$tb->addFormButton($lng->txt("add"), "addNewArea");
		
		return $tb;
	}

	/**
	 * Add new area
	 *
	 * @param
	 * @return
	 */
	function addNewArea()
	{
		global $ilCtrl, $lng;
		
		if ($_POST["shape"] == "Marker")
		{
			$this->content_obj->addTriggerMarker();
			$this->updated = $this->page->update();
			ilUtil::sendSuccess($lng->txt("cont_saved_map_data"), true);
			$ilCtrl->redirect($this, "editMapAreas");
		}
		else
		{
			return parent::addNewArea();
		}
	}
	
	/**
	 * Init area editing form.
	 *
	 * @param        int        $a_mode        Edit Mode
	 */
	public function initAreaEditingForm($a_edit_property)
	{
		global $lng, $ilCtrl;
	
		include_once("Services/Form/classes/class.ilPropertyFormGUI.php");
		$form = new ilPropertyFormGUI();
		$form->setOpenTag(false);
		$form->setCloseTag(false);

		// name
		if ($a_edit_property != "link" && $a_edit_property != "shape")
		{ 
			$ti = new ilTextInputGUI($lng->txt("cont_name"), "area_name");
			$ti->setMaxLength(200);
			$ti->setSize(20);
			$ti->setRequired(true);
			$form->addItem($ti);
		}
		
		// save and cancel commands
		if ($a_edit_property == "")
		{
			$form->setTitle($lng->txt("cont_new_trigger_area"));
			$form->addCommandButton("saveArea", $lng->txt("save"));
		}
		else
		{
			$form->setTitle($lng->txt("cont_new_area"));
			$form->addCommandButton("saveArea", $lng->txt("save"));
		}
	                
		return $form;
	}

	/**
	 * Save new or updated map area
	 */
	function saveArea()
	{
		global $lng, $ilCtrl;
		
		switch ($_SESSION["il_map_edit_mode"])
		{
			// save edited shape
			case "edit_shape":
				$this->std_alias_item->setShape($_SESSION["il_map_area_nr"],
					$_SESSION["il_map_edit_area_type"], $_SESSION["il_map_edit_coords"]);
				$this->updated = $this->page->update();
				break;

			// save new area
			default:
				$area_type = $_SESSION["il_map_edit_area_type"];
				$coords = $_SESSION["il_map_edit_coords"];
				$this->content_obj->addTriggerArea($this->std_alias_item,
					$area_type, $coords,
					ilUtil::stripSlashes($_POST["area_name"]), $link);
				$this->updated = $this->page->update();
				break;
		}

		//$this->initMapParameters();
		ilUtil::sendSuccess($lng->txt("cont_saved_map_area"), true);
		$ilCtrl->redirect($this, "editMapAreas");
	}
	
	/**
	 * Update trigger
	 */
	function updateTrigger()
	{
		global $lng, $ilCtrl;
		
		$this->content_obj->setTriggerOverlays($_POST["ov"]);
		$this->content_obj->setTriggerPopups($_POST["pop"]);
		$this->content_obj->setTriggerOverlayPositions($_POST["ovpos"]);
		$this->content_obj->setTriggerMarkerPositions($_POST["markpos"]);
		$this->content_obj->setTriggerTitles($_POST["title"]);
		$this->updated = $this->page->update();
		ilUtil::sendSuccess($lng->txt("msg_obj_modified"), true);
		$ilCtrl->redirect($this, "editMapAreas");
	}
	
	/**
	 * Confirm trigger deletion
	 */
	function confirmDeleteTrigger()
	{
		global $ilCtrl, $tpl, $lng;
			
		if (!is_array($_POST["tr"]) || count($_POST["tr"]) == 0)
		{
			ilUtil::sendFailure($lng->txt("no_checkbox"), true);
			$ilCtrl->redirect($this, "editMapAreas");
		}
		else
		{
			include_once("./Services/Utilities/classes/class.ilConfirmationGUI.php");
			$cgui = new ilConfirmationGUI();
			$cgui->setFormAction($ilCtrl->getFormAction($this));
			$cgui->setHeaderText($lng->txt("cont_really_delete_triggers"));
			$cgui->setCancel($lng->txt("cancel"), "editMapAreas");
			$cgui->setConfirm($lng->txt("delete"), "deleteTrigger");
			
			foreach ($_POST["tr"] as $i)
			{
				$cgui->addItem("tr[]", $i, $_POST["title"][$i]);
			}
			
			$tpl->setContent($cgui->getHTML());
		}
	}

	/**
	 * Delete trigger
	 */
	function deleteTrigger()
	{
		global $ilCtrl, $lng;
		
		if (is_array($_POST["tr"]) && count($_POST["tr"]) > 0)
		{
			foreach ($_POST["tr"] as $tr_nr)
			{
				$this->content_obj->deleteTrigger($this->std_alias_item, $tr_nr);
			}
			$this->updated = $this->page->update();
			ilUtil::sendSuccess($lng->txt("cont_areas_deleted"), true);
		}

		$ilCtrl->redirect($this, "editMapAreas");
	}

}
?>