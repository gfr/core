<?php
/**
 * Routes.
 *
 * @copyright Zikula contributors (Zikula)
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @author Zikula contributors <info@ziku.la>.
 * @link https://ziku.la
 * @link https://ziku.la
 * @version Generated by ModuleStudio 1.2.0 (https://modulestudio.de).
 */

namespace Zikula\RoutesModule\Base;

use Symfony\Component\Validator\Constraints as Assert;
use Zikula\ExtensionsModule\Api\ApiInterface\VariableApiInterface;

/**
 * Application settings class for handling module variables.
 */
abstract class AbstractAppSettings
{
    /**
     * @var VariableApiInterface
     */
    protected $variableApi;
    
    /**
     * The amount of routes shown per page
     *
     * @Assert\Type(type="integer")
     * @Assert\NotBlank()
     * @Assert\NotEqualTo(value=0)
     * @Assert\LessThan(value=100000000000)
     * @var integer $routeEntriesPerPage
     */
    protected $routeEntriesPerPage = 10;
    
    
    /**
     * AppSettings constructor.
     *
     * @param VariableApiInterface $variableApi VariableApi service instance
     */
    public function __construct(
        VariableApiInterface $variableApi
    ) {
        $this->variableApi = $variableApi;
    
        $this->load();
    }
    
    /**
     * Returns the route entries per page.
     *
     * @return integer
     */
    public function getRouteEntriesPerPage()
    {
        return $this->routeEntriesPerPage;
    }
    
    /**
     * Sets the route entries per page.
     *
     * @param integer $routeEntriesPerPage
     *
     * @return void
     */
    public function setRouteEntriesPerPage($routeEntriesPerPage)
    {
        if (intval($this->routeEntriesPerPage) !== intval($routeEntriesPerPage)) {
            $this->routeEntriesPerPage = intval($routeEntriesPerPage);
        }
    }
    
    
    /**
     * Loads module variables from the database.
     */
    protected function load()
    {
        $moduleVars = $this->variableApi->getAll('ZikulaRoutesModule');
    
        if (isset($moduleVars['routeEntriesPerPage'])) {
            $this->setRouteEntriesPerPage($moduleVars['routeEntriesPerPage']);
        }
    }
    
    /**
     * Saves module variables into the database.
     */
    public function save()
    {
        $this->variableApi->set('ZikulaRoutesModule', 'routeEntriesPerPage', $this->getRouteEntriesPerPage());
    }
}
