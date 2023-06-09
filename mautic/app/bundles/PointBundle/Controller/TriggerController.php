<?php

namespace Mautic\PointBundle\Controller;

use Mautic\CoreBundle\Controller\FormController;
use Mautic\CoreBundle\Factory\PageHelperFactoryInterface;
use Mautic\PointBundle\Entity\Trigger;
use Mautic\PointBundle\Model\TriggerEventModel;
use Mautic\PointBundle\Model\TriggerModel;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class TriggerController extends FormController
{
    /**
     * @param int $page
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function indexAction($page = 1)
    {
        //set some permissions
        $permissions = $this->get('mautic.security')->isGranted([
            'point:triggers:view',
            'point:triggers:create',
            'point:triggers:edit',
            'point:triggers:delete',
            'point:triggers:publish',
        ], 'RETURN_ARRAY');

        if (!$permissions['point:triggers:view']) {
            return $this->accessDenied();
        }

        $this->setListFilters();

        /** @var PageHelperFactoryInterface $pageHelperFacotry */
        $pageHelperFacotry = $this->get('mautic.page.helper.factory');
        $pageHelper        = $pageHelperFacotry->make('mautic.point.trigger', $page);

        $limit      = $pageHelper->getLimit();
        $start      = $pageHelper->getStart();
        $search     = $this->request->get('search', $this->get('session')->get('mautic.point.trigger.filter', ''));
        $filter     = ['string' => $search, 'force' => []];
        $orderBy    = $this->get('session')->get('mautic.point.trigger.orderby', 't.name');
        $orderByDir = $this->get('session')->get('mautic.point.trigger.orderbydir', 'ASC');
        $triggers   = $this->getModel('point.trigger')->getEntities(
            [
                'start'      => $start,
                'limit'      => $limit,
                'filter'     => $filter,
                'orderBy'    => $orderBy,
                'orderByDir' => $orderByDir,
            ]
        );

        $this->get('session')->set('mautic.point.trigger.filter', $search);

        $count = count($triggers);
        if ($count && $count < ($start + 1)) {
            $lastPage  = $pageHelper->countPage($count);
            $returnUrl = $this->generateUrl('mautic_pointtrigger_index', ['page' => $lastPage]);
            $pageHelper->rememberPage($lastPage);

            return $this->postActionRedirect([
                'returnUrl'       => $returnUrl,
                'viewParameters'  => ['page' => $lastPage],
                'contentTemplate' => 'Mautic\PointBundle\Controller\TriggerController::indexAction',
                'passthroughVars' => [
                    'activeLink'    => '#mautic_pointtrigger_index',
                    'mauticContent' => 'pointTrigger',
                ],
            ]);
        }

        $pageHelper->rememberPage($page);

        return $this->delegateView([
            'viewParameters' => [
                'searchValue' => $search,
                'items'       => $triggers,
                'page'        => $page,
                'limit'       => $limit,
                'permissions' => $permissions,
                'tmpl'        => $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'index') : 'index',
            ],
            'contentTemplate' => 'MauticPointBundle:Trigger:list.html.twig',
            'passthroughVars' => [
                'activeLink'    => '#mautic_pointtrigger_index',
                'mauticContent' => 'pointTrigger',
                'route'         => $this->generateUrl('mautic_pointtrigger_index', ['page' => $page]),
            ],
        ]);
    }

    /**
     * View a specific trigger.
     *
     * @param int $objectId
     *
     * @return array|JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function viewAction($objectId)
    {
        $entity = $this->getModel('point.trigger')->getEntity($objectId);

        //set the page we came from
        $page = $this->get('session')->get('mautic.point.trigger.page', 1);

        $permissions = $this->get('mautic.security')->isGranted([
            'point:triggers:view',
            'point:triggers:create',
            'point:triggers:edit',
            'point:triggers:delete',
            'point:triggers:publish',
        ], 'RETURN_ARRAY');

        if (null === $entity) {
            //set the return URL
            $returnUrl = $this->generateUrl('mautic_pointtrigger_index', ['page' => $page]);

            return $this->postActionRedirect([
                'returnUrl'       => $returnUrl,
                'viewParameters'  => ['page' => $page],
                'contentTemplate' => 'Mautic\PointBundle\Controller\TriggerController::indexAction',
                'passthroughVars' => [
                    'activeLink'    => '#mautic_pointtrigger_index',
                    'mauticContent' => 'pointTrigger',
                ],
                'flashes' => [
                    [
                        'type'    => 'error',
                        'msg'     => 'mautic.point.trigger.error.notfound',
                        'msgVars' => ['%id%' => $objectId],
                    ],
                ],
            ]);
        } elseif (!$permissions['point:triggers:view']) {
            return $this->accessDenied();
        }

        return $this->delegateView([
            'viewParameters' => [
                'entity'      => $entity,
                'page'        => $page,
                'permissions' => $permissions,
            ],
            'contentTemplate' => 'MauticPointBundle:Trigger:details.html.twig',
            'passthroughVars' => [
                'activeLink'    => '#mautic_pointtrigger_index',
                'mauticContent' => 'pointTrigger',
                'route'         => $this->generateUrl('mautic_pointtrigger_action', [
                        'objectAction' => 'view',
                        'objectId'     => $entity->getId(), ]
                ),
            ],
        ]);
    }

    /**
     * Generates new form and processes post data.
     *
     * @param \Mautic\PointBundle\Entity\Trigger $entity
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function newAction($entity = null)
    {
        /** @var \Mautic\PointBundle\Model\TriggerModel $model */
        $model = $this->getModel('point.trigger');

        if (!($entity instanceof Trigger)) {
            /** @var \Mautic\PointBundle\Entity\Trigger $entity */
            $entity = $model->getEntity();
        }

        $session      = $this->get('session');
        $pointTrigger = $this->request->request->get('pointtrigger', []);
        $sessionId    = $pointTrigger['sessionId'] ?? 'mautic_'.sha1(uniqid(random_int(1, PHP_INT_MAX), true));

        if (!$this->get('mautic.security')->isGranted('point:triggers:create')) {
            return $this->accessDenied();
        }

        //set the page we came from
        $page = $this->get('session')->get('mautic.point.trigger.page', 1);

        //set added/updated events
        $addEvents     = $session->get('mautic.point.'.$sessionId.'.triggerevents.modified', []);
        $deletedEvents = $session->get('mautic.point.'.$sessionId.'.triggerevents.deleted', []);

        $action = $this->generateUrl('mautic_pointtrigger_action', ['objectAction' => 'new']);
        $form   = $model->createForm($entity, $this->get('form.factory'), $action);
        $form->get('sessionId')->setData($sessionId);

        ///Check for a submitted form and process it
        if ('POST' == $this->request->getMethod()) {
            $valid = false;
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    //only save events that are not to be deleted
                    $events = array_diff_key($addEvents, array_flip($deletedEvents));

                    //make sure that at least one action is selected
                    if ('point.trigger' == 'point' && empty($events)) {
                        //set the error
                        $form->addError(new FormError(
                            $this->get('translator')->trans('mautic.core.value.required', [], 'validators')
                        ));
                        $valid = false;
                    } else {
                        $model->setEvents($entity, $events);

                        $model->saveEntity($entity);

                        $this->addFlash('mautic.core.notice.created', [
                            '%name%'      => $entity->getName(),
                            '%menu_link%' => 'mautic_pointtrigger_index',
                            '%url%'       => $this->generateUrl('mautic_pointtrigger_action', [
                                'objectAction' => 'edit',
                                'objectId'     => $entity->getId(),
                            ]),
                        ]);

                        if (!$this->getFormButton($form, ['buttons', 'save'])->isClicked()) {
                            //return edit view so that all the session stuff is loaded
                            return $this->editAction($entity->getId(), true);
                        }
                    }
                }
            }

            if ($cancelled || ($valid && $this->getFormButton($form, ['buttons', 'save'])->isClicked())) {
                $viewParameters = ['page' => $page];
                $returnUrl      = $this->generateUrl('mautic_pointtrigger_index', $viewParameters);
                $template       = 'Mautic\PointBundle\Controller\TriggerController::indexAction';

                //clear temporary fields
                $this->clearSessionComponents($sessionId);

                return $this->postActionRedirect([
                    'returnUrl'       => $returnUrl,
                    'viewParameters'  => $viewParameters,
                    'contentTemplate' => $template,
                    'passthroughVars' => [
                        'activeLink'    => '#mautic_pointtrigger_index',
                        'mauticContent' => 'pointTrigger',
                    ],
                ]);
            }
        } else {
            //clear out existing fields in case the form was refreshed, browser closed, etc
            $this->clearSessionComponents($sessionId);
            $addEvents = $deletedEvents = [];
        }

        return $this->delegateView([
            'viewParameters' => [
                'events'        => $model->getEventGroups(),
                'triggerEvents' => $addEvents,
                'deletedEvents' => $deletedEvents,
                'tmpl'          => $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'index') : 'index',
                'entity'        => $entity,
                'form'          => $form->createView(),
                'sessionId'     => $sessionId,
            ],
            'contentTemplate' => 'MauticPointBundle:Trigger:form.html.twig',
            'passthroughVars' => [
                'activeLink'    => '#mautic_pointtrigger_index',
                'mauticContent' => 'pointTrigger',
                'route'         => $this->generateUrl('mautic_pointtrigger_action', [
                        'objectAction' => (!empty($valid) ? 'edit' : 'new'), //valid means a new form was applied
                        'objectId'     => $entity->getId(), ]
                ),
            ],
        ]);
    }

    /**
     * Generates edit form and processes post data.
     *
     * @param int  $objectId
     * @param bool $ignorePost
     *
     * @return JsonResponse|Response
     */
    public function editAction($objectId, $ignorePost = false)
    {
        /** @var \Mautic\PointBundle\Model\TriggerModel $model */
        $model      = $this->getModel('point.trigger');
        $entity     = $model->getEntity($objectId);
        $session    = $this->get('session');
        $cleanSlate = true;

        //set the page we came from
        $page = $this->get('session')->get('mautic.point.trigger.page', 1);

        //set the return URL
        $returnUrl = $this->generateUrl('mautic_pointtrigger_index', ['page' => $page]);

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'viewParameters'  => ['page' => $page],
            'contentTemplate' => 'Mautic\PointBundle\Controller\TriggerController::indexAction',
            'passthroughVars' => [
                'activeLink'    => '#mautic_pointtrigger_index',
                'mauticContent' => 'pointTrigger',
            ],
        ];

        //form not found
        if (null === $entity) {
            return $this->postActionRedirect(
                array_merge($postActionVars, [
                    'flashes' => [
                        [
                            'type'    => 'error',
                            'msg'     => 'mautic.point.trigger.error.notfound',
                            'msgVars' => ['%id%' => $objectId],
                        ],
                    ],
                ])
            );
        } elseif (!$this->get('mautic.security')->isGranted('point:triggers:edit')) {
            return $this->accessDenied();
        } elseif ($model->isLocked($entity)) {
            //deny access if the entity is locked
            return $this->isLocked($postActionVars, $entity, 'point.trigger');
        }

        $action = $this->generateUrl('mautic_pointtrigger_action', ['objectAction' => 'edit', 'objectId' => $objectId]);
        $form   = $model->createForm($entity, $this->get('form.factory'), $action);
        $form->get('sessionId')->setData($objectId);

        ///Check for a submitted form and process it
        if (!$ignorePost && 'POST' == $this->request->getMethod()) {
            $valid = false;
            if (!$cancelled = $this->isFormCancelled($form)) {
                //set added/updated events
                $addEvents     = $session->get('mautic.point.'.$objectId.'.triggerevents.modified', []);
                $deletedEvents = $session->get('mautic.point.'.$objectId.'.triggerevents.deleted', []);
                $events        = array_diff_key($addEvents, array_flip($deletedEvents));

                if ($valid = $this->isFormValid($form)) {
                    //make sure that at least one field is selected
                    if ('point.trigger' == 'point' && empty($addEvents)) {
                        //set the error
                        $form->addError(new FormError(
                            $this->get('translator')->trans('mautic.core.value.required', [], 'validators')
                        ));
                        $valid = false;
                    } else {
                        $model->setEvents($entity, $events);

                        //form is valid so process the data
                        $model->saveEntity($entity, $this->getFormButton($form, ['buttons', 'save'])->isClicked());

                        //delete entities
                        if (count($deletedEvents)) {
                            $triggerEventModel = $this->getModel('point.triggerevent');
                            \assert($triggerEventModel instanceof TriggerEventModel);
                            $triggerEventModel->deleteEntities($deletedEvents);
                        }

                        $this->addFlash('mautic.core.notice.updated', [
                            '%name%'      => $entity->getName(),
                            '%menu_link%' => 'mautic_pointtrigger_index',
                            '%url%'       => $this->generateUrl('mautic_pointtrigger_action', [
                                'objectAction' => 'edit',
                                'objectId'     => $entity->getId(),
                            ]),
                        ]);
                    }
                }
            } else {
                //unlock the entity
                $model->unlockEntity($entity);
            }

            if ($cancelled || ($valid && $this->getFormButton($form, ['buttons', 'save'])->isClicked())) {
                $viewParameters = ['page' => $page];
                $returnUrl      = $this->generateUrl('mautic_pointtrigger_index', $viewParameters);
                $template       = 'Mautic\PointBundle\Controller\TriggerController::indexAction';

                //remove fields from session
                $this->clearSessionComponents($objectId);

                return $this->postActionRedirect(
                    array_merge($postActionVars, [
                        'returnUrl'       => $returnUrl,
                        'viewParameters'  => $viewParameters,
                        'contentTemplate' => $template,
                    ])
                );
            } elseif ($form->get('buttons')->get('apply')->isClicked()) {
                //rebuild everything to include new ids
                $cleanSlate = true;
            }
        } else {
            $cleanSlate = true;

            //lock the entity
            $model->lockEntity($entity);
        }

        if ($cleanSlate) {
            //clean slate
            $this->clearSessionComponents($objectId);

            //load existing events into session
            $triggerEvents   = [];
            $existingActions = $entity->getEvents()->toArray();
            foreach ($existingActions as $a) {
                $id     = $a->getId();
                $action = $a->convertToArray();
                unset($action['form']);
                $triggerEvents[$id] = $action;
            }
            $session->set('mautic.point.'.$objectId.'.triggerevents.modified', $triggerEvents);
            $deletedEvents = [];
        }

        return $this->delegateView([
            'viewParameters' => [
                'events'        => $model->getEventGroups(),
                'triggerEvents' => $triggerEvents,
                'deletedEvents' => $deletedEvents,
                'tmpl'          => $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'index') : 'index',
                'entity'        => $entity,
                'form'          => $form->createView(),
                'sessionId'     => $objectId,
            ],
            'contentTemplate' => 'MauticPointBundle:Trigger:form.html.twig',
            'passthroughVars' => [
                'activeLink'    => '#mautic_pointtrigger_index',
                'mauticContent' => 'pointTrigger',
                'route'         => $this->generateUrl('mautic_pointtrigger_action', [
                        'objectAction' => 'edit',
                        'objectId'     => $entity->getId(), ]
                ),
            ],
        ]);
    }

    /**
     * Clone an entity.
     *
     * @param int $objectId
     *
     * @return array|JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function cloneAction($objectId)
    {
        $model  = $this->getModel('point.trigger');
        $entity = $model->getEntity($objectId);

        if (null != $entity) {
            if (!$this->get('mautic.security')->isGranted('point:triggers:create')) {
                return $this->accessDenied();
            }

            $entity = clone $entity;
            $entity->setIsPublished(false);
        }

        return $this->newAction($entity);
    }

    /**
     * Deletes the entity.
     *
     * @param $objectId
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteAction($objectId)
    {
        $page      = $this->get('session')->get('mautic.point.trigger.page', 1);
        $returnUrl = $this->generateUrl('mautic_pointtrigger_index', ['page' => $page]);
        $flashes   = [];

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'viewParameters'  => ['page' => $page],
            'contentTemplate' => 'Mautic\PointBundle\Controller\TriggerController::indexAction',
            'passthroughVars' => [
                'activeLink'    => '#mautic_pointtrigger_index',
                'mauticContent' => 'pointTrigger',
            ],
        ];

        if (Request::METHOD_POST === $this->request->getMethod()) {
            $model = $this->getModel('point.trigger');
            \assert($model instanceof TriggerModel);
            $entity = $model->getEntity($objectId);

            if (null === $entity) {
                $flashes[] = [
                    'type'    => 'error',
                    'msg'     => 'mautic.point.trigger.error.notfound',
                    'msgVars' => ['%id%' => $objectId],
                ];
            } elseif (!$this->get('mautic.security')->isGranted('point:triggers:delete')) {
                return $this->accessDenied();
            } elseif ($model->isLocked($entity)) {
                return $this->isLocked($postActionVars, $entity, 'point.trigger');
            }

            $model->deleteEntity($entity);

            $identifier = $this->get('translator')->trans($entity->getName());
            $flashes[]  = [
                'type'    => 'notice',
                'msg'     => 'mautic.core.notice.deleted',
                'msgVars' => [
                    '%name%' => $identifier,
                    '%id%'   => $objectId,
                ],
            ];
        } //else don't do anything

        return $this->postActionRedirect(
            array_merge($postActionVars, [
                'flashes' => $flashes,
            ])
        );
    }

    /**
     * Deletes a group of entities.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function batchDeleteAction()
    {
        $page      = $this->get('session')->get('mautic.point.trigger.page', 1);
        $returnUrl = $this->generateUrl('mautic_pointtrigger_index', ['page' => $page]);
        $flashes   = [];

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'viewParameters'  => ['page' => $page],
            'contentTemplate' => 'Mautic\PointBundle\Controller\TriggerController::indexAction',
            'passthroughVars' => [
                'activeLink'    => '#mautic_pointtrigger_index',
                'mauticContent' => 'pointTrigger',
            ],
        ];

        if (Request::METHOD_POST === $this->request->getMethod()) {
            $model = $this->getModel('point.trigger');
            \assert($model instanceof TriggerModel);
            $ids       = json_decode($this->request->query->get('ids', '{}'));
            $deleteIds = [];

            // Loop over the IDs to perform access checks pre-delete
            foreach ($ids as $objectId) {
                $entity = $model->getEntity($objectId);

                if (null === $entity) {
                    $flashes[] = [
                        'type'    => 'error',
                        'msg'     => 'mautic.point.trigger.error.notfound',
                        'msgVars' => ['%id%' => $objectId],
                    ];
                } elseif (!$this->get('mautic.security')->isGranted('point:triggers:delete')) {
                    $flashes[] = $this->accessDenied(true);
                } elseif ($model->isLocked($entity)) {
                    $flashes[] = $this->isLocked($postActionVars, $entity, 'point.trigger', true);
                } else {
                    $deleteIds[] = $objectId;
                }
            }

            // Delete everything we are able to
            if (!empty($deleteIds)) {
                $entities = $model->deleteEntities($deleteIds);

                $flashes[] = [
                    'type'    => 'notice',
                    'msg'     => 'mautic.point.trigger.notice.batch_deleted',
                    'msgVars' => [
                        '%count%' => count($entities),
                    ],
                ];
            }
        } //else don't do anything

        return $this->postActionRedirect(
            array_merge($postActionVars, [
                'flashes' => $flashes,
            ])
        );
    }

    /**
     * Clear field and events from the session.
     */
    public function clearSessionComponents($sessionId)
    {
        $session = $this->get('session');
        $session->remove('mautic.point.'.$sessionId.'.triggerevents.modified');
        $session->remove('mautic.point.'.$sessionId.'.triggerevents.deleted');
    }
}
