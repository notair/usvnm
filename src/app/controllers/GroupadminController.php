<?php
/**
 * Group management controller's.
 *
 * @author Team USVN <contact@usvn.info>
 * @link http://www.usvn.info
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2-en.txt CeCILL V2
 * @copyright Copyright 2007, Team USVN
 * @since 0.5
 * @package admin
 * @subpackage group
 *
 * This software has been written at EPITECH <http://www.epitech.net>
 * EPITECH, European Institute of Technology, Paris - FRANCE -
 * This group has been realised as part of
 * end of studies group.
 *
 * $Id$
 */

require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'AdminadminController.php';

class GroupadminController extends AdminadminController
{
	public function checkRole()
	{
		$user = $this->getRequest()->getParam('user');
		if($user->is_admin)
		{
			return;
		}
		$identity = Zend_Auth::getInstance()->getIdentity();

		$user_table = new USVN_Db_Table_Users();
		$users = $user_table->fetchRow(array('users_login = ?' => $identity['username']));

		$group_name = str_replace(USVN_URL_SEP, '/', $this->getRequest()->getParam('name'));
		$table = new USVN_Db_Table_Groups();
		$this->view->group = $table->fetchRow(array('groups_name = ?' => $group_name));

		$table = new USVN_Db_Table_UsersToGroups();
		$UserToGroup = $table->fetchRow(array('users_id = ?' => $users->users_id, 'groups_id = ?' => $this->view->group->groups_id));
		if($UserToGroup==null)
		{
			$this->_redirect("/admin/group");
		}
		if($UserToGroup->is_leader!="1")
		{
			$this->_redirect("/admin/group");
		}
	}

	public static function getGroupData($data)
	{
		if (!isset($data["groups_name"]) || !isset($data["groups_description"])) {
			return array();
		}

		$group = array();
		$group["groups_name"] = $data["groups_name"];
		$group["groups_description"] = $data["groups_description"];
		return $group;
	}

	public function indexAction()
	{
		$table = new USVN_Db_Table_Groups();
		$user = $this->getRequest()->getParam('user');
		if($user->is_admin)
		{	
			$this->view->groups = $table->fetchAll(null, "groups_name");
		}else{
			$this->view->groups = $table->fetchAllForUser($user, "groups_name");
		}
	}

	public function newAction()
	{
		$user = $this->getRequest()->getParam('user');
//		if(!$user->is_admin)
//			$this->_redirect("/admin/group");
		$table = new USVN_Db_Table_Groups();
		$this->view->group = $table->createRow();
		$table = new USVN_Db_Table_Users();
		$this->view->users = $table->fetchAll(null, "users_login");
	}

	public function createAction()
	{
                $user = $this->getRequest()->getParam('user');
                //if(!$user->is_admin)
                //        $this->_redirect("/admin/group");
	
		$data = $this->getGroupData($_POST);
		if (empty($data)) {
			$this->_redirect("/admin/group/new");
		}
		$table = new USVN_Db_Table_Groups();
		$group = $table->createRow($data);
		$this->view->group = $group;
		if ($table->isAGroup($data['groups_name'])) {
			$this->view->message = sprintf(T_("Group %s already exist"), $data['groups_name']);
			$this->render('new');
			return;
		}
		try {
			$group->save();
			//foreach ($_POST['users'] as $user) {
			//	$group->addUser($user);
			//}
			$user = $this->getRequest()->getParam('user');
			$group->addUser($user, true);
			$group->save();
			$this->_redirect("/admin/group/");
		}
		catch (USVN_Exception $e) {
			$this->view->message = $e->getMessage();
			$table = new USVN_Db_Table_Users();
			$this->view->users = $table->fetchAll(null, "users_login");
			$this->render('new');
		}
	}

	public function editAction()
	{
		$this->checkRole();
		$group_name = str_replace(USVN_URL_SEP, '/', $this->getRequest()->getParam('name'));
		$table = new USVN_Db_Table_Groups();
		$this->view->group = $table->fetchRow(array('groups_name = ?' => $group_name));
		if ($this->view->group === null) {
			throw new USVN_Exception(T_("Invalid group %s."), $group_name);
		}
		$table = new USVN_Db_Table_Users();
		$this->view->users = $table->fetchAll(null, "users_login");
	}

	public function updateAction()
	{
		$data = $this->getGroupData($_POST);
		if (empty($data)) {
			$this->_redirect("/admin/group/");
		}
		$group_name = str_replace(USVN_URL_SEP, '/', $this->getRequest()->getParam('name'));
		$table = new USVN_Db_Table_Groups();
		$group = $table->fetchRow(array("groups_name = ?" => $group_name));
		if ($group === null) {
			throw new USVN_Exception(T_("Invalid group %s."), $group_name);
		}
		$group->setFromArray($data);
		try {
			$group->save();
			$this->_redirect("/admin/group/");
		}
		catch (USVN_Exception $e) {
			$this->view->group = $group;
			$this->view->message = $e->getMessage();

			$table = new USVN_Db_Table_Users();
			$this->view->users = $table->fetchAll(null, "users_login");

			$this->render('edit');
		}
	}

	public function deleteAction()
	{
		$this->checkRole();
		$table = new USVN_Db_Table_Groups();
		$group_name = str_replace(USVN_URL_SEP, '/', $this->getRequest()->getParam('name'));
		$group = $table->fetchRow(array('groups_name = ?' => $group_name));
		if ($group === null) {
			throw new USVN_Exception(T_("Invalid group %s."), $group_name);
		}
		$group->delete();
		$this->_redirect("/admin/group/");
	}
}
