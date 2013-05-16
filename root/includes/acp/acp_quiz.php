<?php
// Ultimate Quiz MOD ACP Module

if (!defined('IN_PHPBB'))
{
	exit;
}

class acp_quiz
{
	var $u_action;
	
	function main($id, $mode)
	{
		global $db, $user, $auth, $cache, $template, $config, $phpbb_root_path, $phpbb_admin_path, $phpEx, $u_action;

		// Include quiz functions
		if( file_exists($phpbb_root_path . 'includes/quiz/quiz.' . $phpEx) )
		{
			global $table_prefix;
			include($phpbb_root_path . 'includes/quiz/quiz.' . $phpEx);
		}

		// Include usergroup functions
		include($phpbb_root_path . 'includes/functions_user.' . $phpEx);

		$quiz_configuration = new quiz_configuration;
		$quiz_configuration->load();

		$user->add_lang('acp/quiz');
		$type = request_var('t', '');

		switch($type)
		{
			// Edit a quiz category
			case 'edit_category':
				$category_id = request_var('quiz', 0);
				$edit_category_link = append_sid("{$phpbb_admin_path}index.{$phpEx}", "i=quiz&amp;t=edit_category&amp;quiz=$category_id");

				// Make the database update
				if( !empty($_POST['submit']) )
				{
					if( !check_form_key('uqm_category_edit') )
					{
						trigger_error($user->lang['ACP_UQM_QUIZ_FORM_INVALID'] . adm_back_link($edit_category_link));
					}

					// Values to update, which we'll pass by reference to the validation checking function.
					$category_name = request_var('category_name', '');
					$group_rewards_destination_group_id = null;
					$group_rewards_percentage = null;
					
					// Group permissions
					$group_permissions_groups = request_var('permitted_groups', array(0));
					$group_permissions_groups = implode(', ', $group_permissions_groups);

					// Run through all of the validations. This will display an error page if there are failed validations.
					$this->run_category_validations(
						$category_name, 
						$group_rewards_percentage, 
						$group_rewards_destination_group_id, 
						$edit_category_link,
						$category_id
					);

					// Category description
					$category_description = request_var('category_description', '');

					// Perform an update query for the current quiz category
					$category_array = array(
						'quiz_category_name' 							=> utf8_normalize_nfc($category_name),
						'quiz_category_description'						=> utf8_normalize_nfc($category_description),
						'quiz_category_destination_group_percentage'	=> $group_rewards_percentage,
						'quiz_category_destination_group_id'			=> $group_rewards_destination_group_id,
						'quiz_category_group_ids'						=> $group_permissions_groups // Group permissions
					);

					$sql = 'UPDATE ' . QUIZ_CATEGORIES_TABLE . '
							SET ' . $db->sql_build_array('UPDATE', $category_array) . '
							WHERE quiz_category_id = ' . $category_id;

					$db->sql_query($sql);

					// Updated successfully
					trigger_error(
						sprintf($user->lang['ACP_UQM_CATEGORY_UPDATED'], $category_name) . adm_back_link($this->u_action)
					);					
				}

				// Deal with the form key
				add_form_key('uqm_category_edit');

				$sql = 'SELECT * 
						FROM ' . QUIZ_CATEGORIES_TABLE . '
						WHERE quiz_category_id = ' . $category_id;
				
				$result	= $db->sql_query_limit($sql, 1);
				$row	= $db->sql_fetchrow($result);

				$category_name 			= $row['quiz_category_name'];
				$category_description	= $row['quiz_category_description'];
				$destination_group		= $row['quiz_category_destination_group_id'];
				$group_percentage		= $row['quiz_category_destination_group_percentage'];
				$permitted_groups		= explode(', ', $row['quiz_category_group_ids']); // Group permissions

				$db->sql_freeresult($result);

				$template->assign_vars( array(
					'S_FORM_ACTION'				=> $edit_category_link,
					'U_CATEGORY_VALUE'			=> $category_name,
					'U_CATEGORY_DESCRIPTION'	=> $category_description,
					'U_GROUP_REWARDS'			=> (isset($destination_group) && isset($group_percentage)),
					'U_GROUP_PERCENTAGE'		=> $group_percentage,
					'U_GROUP_LIST'				=> $this->create_usergroup_list($destination_group),

					// Have the currently chosen groups appear automatically selected for the group permissions
					'U_MULTI_GROUP_LIST'		=> $this->multi_group_select_options($permitted_groups)
				));
				
				$this->tpl_name = 'acp_quiz_category';
				$this->page_title = 'ACP_UQM_QUIZ';

				break;

			// Add a new quiz category
			case 'add_category':
				$add_category_link = append_sid("{$phpbb_admin_path}index.{$phpEx}", "i=quiz&amp;t=add_category");

				// Insert a new quiz category into the database
				if( !empty($_POST['submit']) )
				{
					if( !check_form_key('uqm_category') )
					{
						trigger_error($user->lang['ACP_UQM_QUIZ_FORM_INVALID'] . adm_back_link($add_category_link));
					}

					// The values we'll be inserting. We'll pass these by reference to the validation function
					// so that they can be assigned values or updated if necessary. For example, there is no need to 
					// update the group rewards variables unless the admin has enabled that for this category.
					$category_name = request_var('category_name', '');
					$group_rewards_destination_group_id = null;
					$group_rewards_percentage = null;
					
					// Group permissions
					$group_permissions_groups = request_var('permitted_groups', array(0));
					$group_permissions_groups = implode(', ', $group_permissions_groups);

					// Run through all of the validations. This will display an error page if there are failed validations.
					$this->run_category_validations(
						$category_name, 
						$group_rewards_percentage, 
						$group_rewards_destination_group_id, 
						$add_category_link
					);

					// Category description
					$category_description = request_var('category_description', '');

					// Get ready to perform the query...
					$category_array = array(
						'quiz_category_name' 							=> utf8_normalize_nfc($category_name),
						'quiz_category_description'						=> utf8_normalize_nfc($category_description),
						'quiz_category_destination_group_percentage'	=> $group_rewards_percentage,
						'quiz_category_destination_group_id'			=> $group_rewards_destination_group_id,
						'quiz_category_group_ids'						=> $group_permissions_groups // Group permissions
					);

					$sql = 'INSERT INTO ' . QUIZ_CATEGORIES_TABLE . ' ' . $db->sql_build_array('INSERT', $category_array);
					$db->sql_query($sql);

					// Successfully inserted message
					trigger_error(
						sprintf($user->lang['ACP_UQM_CATEGORY_ADDED'], $category_name) . adm_back_link($this->u_action)
					);
				}

				// Prepare the page... but first deal with the form key
				add_form_key('uqm_category');

				$template->assign_vars( array(
					'S_FORM_ACTION'			=> $add_category_link,
					'U_CATEGORY_VALUE'		=> '',
					'U_GROUP_REWARDS'		=> false,
					'U_GROUP_PERCENTAGE'	=> '',
					'U_GROUP_LIST'			=> $this->create_usergroup_list(),

 					// Group permissions, default to registered users when adding a new category
					'U_MULTI_GROUP_LIST'	=> $this->multi_group_select_options(array(2))
				));
				
				$this->tpl_name = 'acp_quiz_category';
				$this->page_title = 'ACP_UQM_QUIZ';

				break;

			// We won't put a break on this case so that if the user clicks "no" they will see the default.
			case 'delete_category':
			$category_id = request_var('quiz', 0);

				if( confirm_box(true) )
				{
					// Get list of questions in the category
					$sql = 'SELECT w.question_id 
						FROM ' . QUIZ_QUESTIONS_TABLE . ' w, ' . QUIZ_TABLE . ' q
						WHERE w.question_quiz = q.quiz_id
						AND q.quiz_category = ' . $category_id;
					$result = $db->sql_query($sql);

					$question_id_list = array();

					while( $row = $db->sql_fetchrow($result) )
					{
						$question_id_list[] = $row['question_id']; 
					}

					$db->sql_freeresult($result);

					$delete_sql = array();
					$delete_sql[] = 'DELETE FROM ' . QUIZ_TABLE . ' 
						WHERE quiz_category = ' . $category_id;

					// If there were no quizzes in the category, we don't have any questions or statistics to delete
					if (sizeof($question_id_list) > 0)
					{
						$delete_sql[] = 'DELETE FROM ' . QUIZ_QUESTIONS_TABLE . '
							WHERE ' . $db->sql_in_set('question_id', $question_id_list);

						$delete_sql[] = 'DELETE FROM ' . QUIZ_STATISTICS_TABLE . '
							WHERE ' . $db->sql_in_set('quiz_question_id', $question_id_list);
					}

					$delete_sql[] = 'DELETE FROM ' . QUIZ_CATEGORIES_TABLE . '
						WHERE quiz_category_id = ' . $category_id;
					
					foreach ($delete_sql as $query)
					{
						$db->sql_query($query);
					}

					trigger_error($user->lang['ACP_UQM_DELETE_SUCCESSFUL'] . adm_back_link($this->u_action));
				}

				else 
				{
					confirm_box(false, 'ACP_UQM_DELETE_TITLE');
				}
				
			default:

			// If the configuration values have been updated, then do some updating...
			if (!empty($_POST['submit']))
			{
				$configuration_list = $quiz_configuration->config_array();
				foreach ($configuration_list as $name)
				{
					// Check the type of setting, and use that as a parameter
					$type = ($quiz_configuration->value($name, true) == 'radio') ? 0 : '';
					$new_value = utf8_normalize_nfc(request_var($name, $type));
					$quiz_configuration->update($name, $new_value);
				}

				$message = $user->lang['ACP_UQM_CONFIG_UPDATED'] . adm_back_link($this->u_action);
				trigger_error($message);
			}

			$this->tpl_name = 'acp_quiz';
			$this->page_title = 'ACP_UQM_QUIZ';

			// Get the category list
			$sql = 'SELECT * FROM ' . QUIZ_CATEGORIES_TABLE . ' ORDER BY quiz_category_name ASC';
			$result = $db->sql_query($sql);

			while( $row = $db->sql_fetchrow($result) )
			{
				$category_name	= $row['quiz_category_name'];
				$category_id	= $row['quiz_category_id'];

				$template->assign_block_vars('category_row', array(
					'CATEGORY_NAME'		=> $category_name,
					'EDIT_LINK'		=> append_sid("{$phpbb_admin_path}index.$phpEx", 'i=quiz&amp;t=edit_category&amp;quiz=' . $category_id),
					'DELETE_LINK'		=> append_sid("{$phpbb_admin_path}index.$phpEx", 'i=quiz&amp;t=delete_category&amp;quiz=' . $category_id),
				));
			}

			$db->sql_freeresult($result);

			// Get the configuration values
			$configuration_list = $quiz_configuration->config_array();

			foreach ($configuration_list as $name)
			{
				$lang_name 		= $user->lang['ACP_UQM_CONFIG_DEFINITIONS'][$name];
				$lang_explain 	= $user->lang['ACP_UQM_CONFIG_DEFINITIONS'][$name . '_explain'];

				$template->assign_block_vars('configuration_row', array(
					'CONFIGURATION_LANG'			=> $lang_name,
					'CONFIGURATION_LANG_EXPLAIN'	=> $lang_explain,

					'CONFIGURATION_NAME'	=> $name,
					'CONFIGURATION_VALUE'	=> $quiz_configuration->value($name),
					'CONFIGURATION_TYPE'	=> $quiz_configuration->value($name, true),
				));
			}

			$template->assign_vars( array(
				'S_FORM_ACTION'		=> append_sid($this->u_action),
				'U_ADD_CATEGORY'	=> sprintf($user->lang['ACP_UQM_ADD_CATEGORY'], '<a href="' . append_sid("{$phpbb_admin_path}index.$phpEx", 'i=quiz&amp;t=add_category') . '">', '</a>'),
			));
		}
	}

	// Do all of the category validation checks (required for both add and edit category pages). Pass most by reference.
	function run_category_validations(&$category_name, &$group_rewards_percentage, &$group_rewards_destination_group_id, $return_link, $category_id = null)
	{
		global $user;

		// Category name validation (required field). We pass the category id in because if the name hasn't changed
		// we don't want it finding a conflict with itself.
		$category_validation = $this->category_validation($category_name, $category_id);

		if ($category_validation !== true)
		{
			// Inform the user that validation failed
			trigger_error($user->lang[$category_validation] . adm_back_link($return_link), E_USER_WARNING);
		}

		// Look at group rewards (these are optional for categories)
		$group_rewards_enabled = request_var('group_rewards_enabled', false);

		if ($group_rewards_enabled)
		{
			$group_rewards_destination_group_id = request_var('group_rewards_group_id', 0);
			$group_rewards_percentage = request_var('group_rewards_percentage', -1);

			// Make sure the user supplied values pass validation
			$group_rewards_validation = $this->group_rewards_validation(
				$group_rewards_percentage, 
				$group_rewards_destination_group_id
			);

			if ($group_rewards_validation !== true)
			{
				// Inform the user that validation failed
				trigger_error($user->lang[$group_rewards_validation] . adm_back_link($return_link), E_USER_WARNING);
			}
		}
	}

	// Check that the category passes validation
	function category_validation($category_name, $category_id = null)
	{
		global $db;

		// Category name is empty
		if (strlen($category_name) < 1)
		{
			return 'ACP_UQM_CATEGORY_NAME_VALIDATE';
		}

		else
		{
			$filtered_category_name = $db->sql_escape(utf8_normalize_nfc($category_name));

			$sql = 'SELECT COUNT(quiz_category_id) AS count_names FROM ' . QUIZ_CATEGORIES_TABLE . "
					WHERE quiz_category_name = '$filtered_category_name'";
			$sql .= (isset($category_id)) ? ' AND quiz_category_id != ' . (int) $category_id : '';
		
			$result = $db->sql_query($sql);
			$category_count = $db->sql_fetchfield('count_names');
		
			// Category name already exists
			if ($category_count > 0)
			{
				return 'ACP_UQM_CATEGORY_NAME_VALIDATE';
			}
		}

		// Passed validation, everything is fine
		return true;
	}

	// Check that the percentage is a valid number and the usergroup exists above id 7
	function group_rewards_validation($percentage, $group_id)
	{
		global $db;

		$passed = false;

		$percentage_valid = ($percentage >= 0 && $percentage <= 100) ? true : false;

		// Percentage is invalid
		if (!$percentage_valid)
		{
			return 'ACP_UQM_CATEGORY_GROUP_REWARDS_PERCENTAGE_VALIDATE';
		}

		$usergroup_exists = ($group_id > 7 && (get_group_name($group_id) != '')) ? true : false;

		// Usergroup is invalid
		if (!$usergroup_exists)
		{
			return 'ACP_UQM_CATEGORY_GROUP_REWARDS_GROUP_VALIDATE';
		}

		// Everything is fine
		return true;
	}

	// Make the dropdown menu of usergroups that a user could be moved to
	function create_usergroup_list($default_group_id = null)
	{
		global $db, $user;

		$sql = 'SELECT group_id, group_name 
				FROM ' . GROUPS_TABLE . '
				WHERE group_id > 7';

		$result = $db->sql_query($sql);

		$select = (isset($default_group_id)) ? '<select name="group_rewards_group_id">' : '<select name="group_rewards_group_id" disabled="true">';
		$select .= '	<option value="0">' . $user->lang['ACP_UQM_CATEGORY_GROUP_REWARDS_GROUP_SELECT'] . '</option>';

		// Iterate through the groups
		while ($row = $db->sql_fetchrow($result))
		{
			$selected = (isset($default_group_id) && $row['group_id'] == (int) $default_group_id) ? ' selected="selected"' : '';
			$select .= '	<option value="' . $row['group_id'] . '"' . $selected . '>' . $row['group_name'] . '</option>';
		}

		$select .= '</select>';

		return $select;
	}
	
	// Create a list of <select> options with all available groups.
	// This function keeps multiple groups selected if an array with ids is provided.
	function multi_group_select_options($group_ids)
	{
		global $db, $user;
		
		$group_ids 	= (is_array($group_ids)) ? $group_ids : array($group_ids);
		$sql_where 	= ($user->data['user_type'] == USER_FOUNDER) ? '' : 'WHERE group_founder_manage = 0';
		
		$sql = 'SELECT group_id, group_name, group_type
			FROM ' . GROUPS_TABLE . " 
			$sql_where 
			ORDER BY group_type DESC, group_name ASC";
		$result = $db->sql_query($sql);
	
		$s_group_options = '';
		
		while ($row = $db->sql_fetchrow($result))
		{			
			$selected = (is_array($group_ids) && in_array($row['group_id'], $group_ids)) ? ' selected="selected"' : '';
			$s_group_name = ($row['group_type'] == GROUP_SPECIAL) ? $user->lang['G_' . $row['group_name']] : $row['group_name'];
			$s_group_options .= '<option'  . (($row['group_type'] == GROUP_SPECIAL) ? ' class="sep"' : '') . ' value="' . $row['group_id'] . '"' . $selected . '>' . $s_group_name . '</option>';
		}
		$db->sql_freeresult($result);
	
		return $s_group_options;
	}
}
?>
