<?php
// Ultimate Quiz MOD ACP Module Info

if (!defined('IN_PHPBB'))
{
	exit;
}

/**
* @package module_install
*/
class acp_quiz_info
{
	function module()
	{
		return array(
			'filename'	=> 'acp_quiz',
			'title'		=> 'ACP_UQM_QUIZ',
			'version'	=> '2.1.0',
			'modes'		=> array(
					'quiz'	=> array(
						'title' => 'ACP_UQM_QUIZ', 
						'auth'	=> 'acl_a_board', 
						'cat'	=> array('ACP_UQM_QUIZ'),
					),
       			),
		);
	}

	function install()
	{
		// Empty
	}

	function uninstall()
	{
		// Empty
	}
}

?>
