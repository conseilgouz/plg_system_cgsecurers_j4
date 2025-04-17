<?php
/**
 * @component     Plugin Rsform CG Secure
 * Version			: 2.0.0
 * @license https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 * @copyright (c) 2025 ConseilGouz. All Rights Reserved.
 * @author ConseilGouz
**/

namespace Conseilgouz\Plugin\System\Cgsecurers\Extension;

// No direct access.
defined('_JEXEC') or die();
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\SubscriberInterface;
use ConseilGouz\CGSecure\Helper\Cgipcheck;

class Cgsecurers extends CMSPlugin implements SubscriberInterface
{
    public $myname = 'CGSecureRS';
    public $mymessage = '(rsform) : try to access forms...';
    public $errtype = 'w';	 // warning
    public $cgsecure_params;
    public function __construct(&$subject, $config)
    {
        parent::__construct($subject, $config);
        $this->cgsecure_params = Cgipcheck::getParams();
        $prefixe = $_SERVER['SERVER_NAME'];
        $prefixe = substr(str_replace('www.', '', $prefixe), 0, 2);
        $this->mymessage = $prefixe.$this->errtype.'-'.$this->mymessage;
    }
    public static function getSubscribedEvents(): array
    {
        return [
            'onRsformFrontendBeforeShowForm' => 'onRsformFrontendBeforeShowForm',
        ];
    }
    // Check IP on prepare Forms
    public function onRsformFrontendBeforeShowForm($event): void
    {
        Cgipcheck::check_ip($this, $this->myname);
    }
}
