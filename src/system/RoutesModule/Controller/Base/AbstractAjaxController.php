<?php
/**
 * Routes.
 *
 * @copyright Zikula contributors (Zikula)
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @author Zikula contributors <support@zikula.org>.
 * @link http://www.zikula.org
 * @link http://zikula.org
 * @version Generated by ModuleStudio 1.0.1 (https://modulestudio.de).
 */

namespace Zikula\RoutesModule\Controller\Base;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Zikula\Core\Controller\AbstractController;

/**
 * Ajax controller base class.
 */
abstract class AbstractAjaxController extends AbstractController
{
    
    /**
     * Updates the sort positions for a given list of entities.
     *
     * @param Request $request Current request instance
     *
     * @return JsonResponse
     *
     * @throws AccessDeniedException Thrown if the user doesn't have required permissions
     */
    public function updateSortPositionsAction(Request $request)
    {
        if (!$this->hasPermission('ZikulaRoutesModule::Ajax', '::', ACCESS_EDIT)) {
            throw new AccessDeniedException();
        }
        
        $objectType = $request->request->getAlnum('ot', 'route');
        $itemIds = $request->request->get('identifiers', []);
        $min = $request->request->getInt('min', 0);
        $max = $request->request->getInt('max', 0);
        
        if (!is_array($itemIds) || count($itemIds) < 2 || $max < 1 || $max <= $min) {
            return new JsonResponse($this->__('Error: invalid input.'), JsonResponse::HTTP_BAD_REQUEST);
        }
        
        $entityFactory = $this->get('zikula_routes_module.entity_factory');
        $repository = $entityFactory->getRepository($objectType);
        $sortableFieldMap = [
            'route' => 'sort'
        ];
        
        $sortFieldSetter = 'set' . ucfirst($sortableFieldMap[$objectType]);
        $sortCounter = $min;
        foreach ($itemIds as $itemId) {
            if (empty($itemId) || !is_numeric($itemId)) {
                continue;
            }
            $entity = $repository->selectById($itemId);
            $entity->$sortFieldSetter($sortCounter);
            $sortCounter++;
        }
        
        // save entities back to database
        $entityFactory->getObjectManager()->flush();
        
        // return response
        return new JsonResponse([
            'message' => $this->__('The setting has been successfully changed.')
        ]);
    }
}
