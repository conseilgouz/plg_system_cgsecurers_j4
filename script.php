<?php
/**
* Rsform Check IP Plugin  - Joomla 4.x/5.x plugin 
* Package			: Rsform Checkip Plugin
* copyright 		: Copyright (C) 2025 ConseilGouz. All rights reserved.
* license    		: https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
*/
// No direct access to this file
defined('_JEXEC') or die;
use Joomla\CMS\Cache\CacheControllerFactoryInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Version;
use Joomla\CMS\Filesystem\File;
use Joomla\Filesystem\Folder;
use Joomla\Database\DatabaseInterface;
use Joomla\Log\Log;

class plgsystemcgsecurersInstallerScript
{
	private $min_joomla_version      = '4.0.0';
	private $min_php_version         = '7.4';
    private $min_secure_version      = '3.5.0';
	private $name                    = 'Plugin System CG Secure RS';
	private $exttype                 = 'plugin';
	private $extname                 = 'cgsecurers';
	private $previous_version        = '';
	private $dir           = null;
	private $lang;
	private $installerName = 'plgsystemcgsecurersinstaller';
	public function __construct()
	{
		$this->dir = __DIR__;
		$this->lang = Factory::getApplication()->getLanguage();
		$this->lang->load($this->extname);
	}

    function preflight($type, $parent)
    {
		if ( ! $this->passMinimumJoomlaVersion())
		{
			$this->uninstallInstaller();
			return false;
		}

		if ( ! $this->passMinimumPHPVersion())
		{
			$this->uninstallInstaller();
			return false;
		}
        if (! $this->passMinimumSecureVersion()) {
            $this->uninstallInstaller();
            return false;
        }
		// To prevent installer from running twice if installing multiple extensions
		if ( ! file_exists($this->dir . '/' . $this->installerName . '.xml'))
		{
			return true;
		}
    }
    
    function postflight($type, $parent)
    {
		if (($type=='install') || ($type == 'update')) { // remove obsolete dir/files
			$this->postinstall_cleanup();
		}

		switch ($type) {
            case 'install': $message = Text::_('ISO_POSTFLIGHT_INSTALLED'); break;
            case 'uninstall': $message = Text::_('ISO_POSTFLIGHT_UNINSTALLED'); break;
            case 'update': $message = Text::_('ISO_POSTFLIGHT_UPDATED'); break;
            case 'discover_install': $message = Text::_('ISO_POSTFLIGHT_DISC_INSTALLED'); break;
        }
		return true;
    }
	private function postinstall_cleanup() {

		$db = Factory::getContainer()->get(DatabaseInterface::class);
        $conditions = array(
            $db->qn('type') . ' = ' . $db->q('plugin'),
            $db->qn('element') . ' = ' . $db->q('cgsecurers'),
			$db->qn('folder'). ' = '.$db->q('system')
        );
        $fields = array($db->qn('enabled') . ' = 1');

        $query = $db->getQuery(true);
		$query->update($db->quoteName('#__extensions'))->set($fields)->where($conditions);
		$db->setQuery($query);
        try {
	        $db->execute();
        }
        catch (RuntimeException $e) {
            Log::add('unable to enable plugin rsform cgsecure', Log::ERROR, 'jerror');
        }
		// remove obsolete update sites
		$query = $db->getQuery(true)
			->delete('#__update_sites')
			->where($db->quoteName('location') . ' like "%432473037d.url-de-test.ws/%"');
		$db->setQuery($query);
		$db->execute();
		// Simple Isotope is now on Github
		$query = $db->getQuery(true)
			->delete('#__update_sites')
			->where($db->quoteName('location') . ' like "%conseilgouz.com/updates/plg_rsform_cgsecure%"');
		$db->setQuery($query);
		$db->execute();

	}

	// Check if Joomla version passes minimum requirement
	private function passMinimumJoomlaVersion()
	{
		$j = new Version();
		$version=$j->getShortVersion(); 
		if (version_compare($version, $this->min_joomla_version, '<'))
		{
			Factory::getApplication()->enqueueMessage(
				'Incompatible Joomla version : found <strong>' . $version . '</strong>, Minimum : <strong>' . $this->min_joomla_version . '</strong>',
				'error'
			);

			return false;
		}

		return true;
	}

	// Check if PHP version passes minimum requirement
	private function passMinimumPHPVersion()
	{

		if (version_compare(PHP_VERSION, $this->min_php_version, '<'))
		{
			Factory::getApplication()->enqueueMessage(
					'Incompatible PHP version : found  <strong>' . PHP_VERSION . '</strong>, Minimum <strong>' . $this->min_php_version . '</strong>',
				'error'
			);
			return false;
		}

		return true;
	}
    // Check if CG Secure version passes minimum requirement
    private function passMinimumSecureVersion()
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);
        $query->select('manifest_cache');
        $query->from($db->quoteName('#__extensions'));
        $query->where('name = "CGSecure Library"');
        $db->setQuery($query);
        $res = $db->loadResult();
        if (!$res) {
            echo "You need install CG Secure";
            return false;
        }
        $manifest = json_decode($res, true);
        if ($manifest['version'] < $this->min_secure_version) {
            Factory::getApplication()->enqueueMessage(
                'Incompatible CG Secure version : found  <strong>' . $manifest['version'] . '</strong>, Minimum <strong>' . $this->min_secure_version . '</strong>',
                'error'
            );
            return false;
        }
        return true;
    }
    
	private function uninstallInstaller()
	{
		if ( ! is_dir(JPATH_PLUGINS . '/system/' . $this->installerName)) {
			return;
		}
		$this->delete([
			JPATH_PLUGINS . '/system/' . $this->installerName . '/language',
			JPATH_PLUGINS . '/system/' . $this->installerName,
		]);
		$db = Factory::getContainer()->get(DatabaseInterface::class);
		$query = $db->getQuery(true)
			->delete('#__extensions')
			->where($db->quoteName('element') . ' = ' . $db->quote($this->installerName))
			->where($db->quoteName('folder') . ' = ' . $db->quote('system'))
			->where($db->quoteName('type') . ' = ' . $db->quote('plugin'));
		$db->setQuery($query);
		$db->execute();
		$cachecontroller = Factory::getContainer()->get(CacheControllerFactoryInterface::class)->createCacheController(s);
		$cachecontroller->clean('_system');
	}

    public function delete($files = [])
    {
        foreach ($files as $file) {
            if (is_dir($file)) {
                Folder::delete($file);
            }

            if (is_file($file)) {
                File::delete($file);
            }
        }
    }

}