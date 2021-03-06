<?php

/*
 * This file is part of the Zikula package.
 *
 * Copyright Zikula Foundation - https://ziku.la/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zikula\BlocksModule\Controller;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Zikula\BlocksModule\Api\BlockApi;
use Zikula\BlocksModule\BlockHandlerInterface;
use Zikula\BlocksModule\Entity\BlockEntity;
use Zikula\BlocksModule\Form\Type\BlockType;
use Zikula\Bundle\FormExtensionBundle\Form\Type\DeletionType;
use Zikula\Core\Controller\AbstractController;
use Zikula\Core\Response\Ajax\FatalResponse;
use Zikula\Core\Response\Ajax\ForbiddenResponse;
use Zikula\ThemeModule\Engine\Annotation\Theme;

/**
 * Class BlockController
 * @Route("/admin/block")
 */
class BlockController extends AbstractController
{
    /**
     * @Route("/new")
     * @Theme("admin")
     * @param Request $request
     * @return RedirectResponse|Response
     */
    public function newAction(Request $request)
    {
        $form = $this->createFormBuilder()
            ->add('bkey', ChoiceType::class, [
                'placeholder' => 'Choose a block type',
                'choices' => array_flip($this->get('zikula_blocks_module.api.block')->getAvailableBlockTypes()),
                'label' => 'Block type'
            ])
            ->add('choose', SubmitType::class, [
                'label' => $this->__('Choose'),
                'icon' => 'fa-check',
                'attr' => [
                    'class' => 'btn btn-success'
                ]
            ])
            ->getForm();

        if ($form->handleRequest($request)->isValid()) {
            $bkey = json_encode($form->getData()['bkey']);

            return $this->redirectToRoute('zikulablocksmodule_block_edit', ['bkey' => $bkey]);
        }

        return $this->render('@ZikulaBlocksModule/Admin/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Create a new block or edit an existing block.
     *
     * @Route("/edit/{blockEntity}", requirements={"blockEntity" = "^[1-9]\d*$"})
     * @Theme("admin")
     * @param Request $request
     * @param BlockEntity $blockEntity
     * @return RedirectResponse|Response
     */
    public function editAction(Request $request, BlockEntity $blockEntity = null)
    {
        $accessLevelRequired = ACCESS_EDIT;
        if (null === $blockEntity) {
            $bKey = json_decode($request->query->get('bkey'));
            if (empty($bKey)) {
                return $this->redirectToRoute('zikulablocksmodule_block_new');
            }
            $blockEntity = new BlockEntity(); // sets defaults in constructor
            $blockEntity->setBkey($bKey);
            $accessLevelRequired = ACCESS_ADD;
            $request->attributes->set('blockEntity', $blockEntity);
        }

        if (!$this->hasPermission('ZikulaBlocksModule::', $blockEntity->getBlocktype() . ':' . $blockEntity->getTitle() . ':' . $blockEntity->getBid(), $accessLevelRequired)) {
            throw new AccessDeniedException();
        }

        $blockInstance = $this->get('zikula_blocks_module.api.block')->createInstanceFromBKey($blockEntity->getBkey());
        $blockType = $blockEntity->getBlocktype();
        if (empty($blockType)) {
            $blockEntity->setBlocktype($blockInstance->getType());
        }

        $form = $this->createForm(BlockType::class, $blockEntity, ['locale' => $request->getLocale()]);
        if (($blockInstance instanceof BlockHandlerInterface) && (null !== $blockInstance->getFormClassName())) {
            $form->add('properties', $blockInstance->getFormClassName(), $blockInstance->getFormOptions());
        }
        $form->handleRequest($request);

        list($moduleName) = explode(':', $blockEntity->getBkey());
        if ($form->isSubmitted() && $form->get('save')->isClicked() && $form->isValid()) {
            // sort Filter array so keys are always sequential.
            $filters = $blockEntity->getFilters();
            sort($filters);
            $blockEntity->setFilters($filters);
            /** @var \Doctrine\ORM\EntityManager $em */
            $em = $this->getDoctrine()->getManager();
            $module = $em->getRepository('ZikulaExtensionsModule:ExtensionEntity')->findOneBy(['name' => $moduleName]);
            $blockEntity->setModule($module);
            $em->persist($blockEntity);
            $em->flush();
            $this->addFlash('status', $this->__('Block saved!'));

            return $this->redirectToRoute('zikulablocksmodule_admin_view');
        }
        if ($form->isSubmitted() && $form->get('cancel')->isClicked()) {
            $this->addFlash('status', $this->__('Operation cancelled.'));

            return $this->redirectToRoute('zikulablocksmodule_admin_view');
        }

        return $this->render('@ZikulaBlocksModule/Admin/edit.html.twig', [
            'moduleName' => $moduleName,
            'propertiesFormTemplate' => ($blockInstance instanceof BlockHandlerInterface) ? $blockInstance->getFormTemplate() : null,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/delete/{bid}", requirements={"bid" = "^[1-9]\d*$"})
     * @Theme("admin")
     *
     * Delete a block.
     *
     * @param Request $request
     * @param BlockEntity $blockEntity
     * @return RedirectResponse|Response
     */
    public function deleteAction(Request $request, BlockEntity $blockEntity)
    {
        if (!$this->hasPermission('ZikulaBlocksModule::', $blockEntity->getBkey() . ':' . $blockEntity->getTitle() . ':' . $blockEntity->getBid(), ACCESS_DELETE)) {
            throw new AccessDeniedException();
        }

        $form = $this->createForm(DeletionType::class);

        if ($form->handleRequest($request)->isValid()) {
            if ($form->get('delete')->isClicked()) {
                $em = $this->getDoctrine()->getManager();
                $em->remove($blockEntity);
                $em->flush();
                $this->addFlash('status', $this->__('Done! Block deleted.'));
            } elseif ($form->get('cancel')->isClicked()) {
                $this->addFlash('status', $this->__('Operation cancelled.'));
            }

            return $this->redirectToRoute('zikulablocksmodule_admin_view');
        }

        return $this->render('@ZikulaBlocksModule/Admin/delete.html.twig', [
            'form' => $form->createView(),
            'block' => $blockEntity
        ]);
    }

    /**
     * Ajax method to toggle the active status of a block.
     *
     *  bid int id of block to toggle.
     *
     * @Route("/toggle-active", methods = {"POST"}, options={"expose"=true})
     * @param Request $request
     * @return JsonResponse|FatalResponse|ForbiddenResponse bid or Ajax error
     */
    public function toggleblockAction(Request $request)
    {
        if (!$this->hasPermission('ZikulaBlocksModule::', '::', ACCESS_ADMIN)) {
            return new ForbiddenResponse($this->__('No permission for this action.'));
        }
        $bid = $request->request->get('bid', -1);
        if ($bid == -1) {
            return new FatalResponse($this->__('No block ID passed.'));
        }
        $em = $this->getDoctrine()->getManager();
        $block = $em->find('ZikulaBlocksModule:BlockEntity', $bid);
        $block->setActive(BlockApi::BLOCK_ACTIVE == $block->getActive() ? BlockApi::BLOCK_INACTIVE : BlockApi::BLOCK_ACTIVE);
        $em->flush();

        return new JsonResponse(['bid' => $bid]);
    }

    /**
     * @Route("/view/{bid}", requirements={"bid" = "^[1-9]\d*$"}, options={"expose"=true})
     *
     * Display a block.
     *
     * @param BlockEntity $blockEntity
     * @return Response symfony response object
     */
    public function viewAction(BlockEntity $blockEntity = null)
    {
        return $this->render('@ZikulaBlocksModule/Admin/blockview.html.twig', [
            'block' => $blockEntity,
        ]);
    }
}
