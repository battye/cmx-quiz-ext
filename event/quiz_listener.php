<?php
/**
*
* @package phpBB Extension - Acme Demo
* @copyright (c) 2013 phpBB Group
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace battye\cmxquiz\event;

/**
* @ignore
*/
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
* Event listener
*/
class quiz_listener implements EventSubscriberInterface
{
	static public function getSubscribedEvents()
	{
		return array(
			'core.user_setup' => 'load_language_on_setup',
			'core.page_header' => 'add_page_header_link',
		);
	}

	/* @var \phpbb\config\config */
	protected $config;

	/* @var \phpbb\controller\helper */
	protected $helper;

	/* @var \phpbb\template\template */
	protected $template;

	/**
	* Constructor
	*
	* @param \phpbb\config\config		$config
	* @param \phpbb\controller\helper	$helper		Controller helper object
	* @param \phpbb\template\template	$template	Template object
	*/
	public function __construct(\phpbb\config\config $config, \phpbb\controller\helper $helper, \phpbb\template\template $template)
	{
		$this->config = $config;
		$this->helper = $helper;
		$this->template = $template;
	}

	public function load_language_on_setup($event)
	{
		$lang_set_ext = $event['lang_set_ext'];

		$lang_set_ext[] = [
			'ext_name' => 'battye/cmxquiz',
			'lang_set' => 'common',
        ];

		$event['lang_set_ext'] = $lang_set_ext;
	}

	public function add_page_header_link($event)
	{
		$enabled = ($this->config['cmx_quiz_enabled'] != 0);
		$this->template->assign_vars([
			'CMX_QUIZ_ENABLED' => $enabled,
			'U_CMX_QUIZ_EXT' => $this->helper->route('cmx_quiz_index'),
        ]);
	}
}