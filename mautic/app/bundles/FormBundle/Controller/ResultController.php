<?php

namespace Mautic\FormBundle\Controller;

use Mautic\CoreBundle\Controller\FormController as CommonFormController;
use Mautic\CoreBundle\Factory\PageHelperFactoryInterface;
use Mautic\FormBundle\Helper\FormUploader;
use Mautic\FormBundle\Model\FormModel;
use Mautic\FormBundle\Model\SubmissionModel;
use Mautic\FormBundle\Model\SubmissionResultLoader;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ResultController extends CommonFormController
{
    public function __construct()
    {
        $this->setStandardParameters(
            'form.submission', // model name
            'form:forms', // permission base
            'mautic_form', // route base
            'mautic.formresult', // session base
            'mautic.form.result', // lang string base
            'MauticFormBundle:Result', // template base
            'mautic_form', // activeLink
            'formresult' // mauticContent
        );
    }

    /**
     * @param int $objectId
     * @param int $page
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function indexAction($objectId, $page = 1)
    {
        /** @var FormModel $formModel */
        $formModel      = $this->getModel('form.form');
        $form           = $formModel->getEntity($objectId);
        $session        = $this->get('session');
        $formPage       = $session->get('mautic.form.page', 1);
        $returnUrl      = $this->generateUrl('mautic_form_index', ['page' => $formPage]);
        $viewOnlyFields = $formModel->getCustomComponents()['viewOnlyFields'];

        if (null === $form) {
            //redirect back to form list
            return $this->postActionRedirect(
                [
                    'returnUrl'       => $returnUrl,
                    'viewParameters'  => ['page' => $formPage],
                    'contentTemplate' => 'Mautic\FormBundle\Controller\FormController::indexAction',
                    'passthroughVars' => [
                        'activeLink'    => 'mautic_form_index',
                        'mauticContent' => 'form',
                    ],
                    'flashes' => [
                        [
                            'type'    => 'error',
                            'msg'     => 'mautic.form.error.notfound',
                            'msgVars' => ['%id%' => $objectId],
                        ],
                    ],
                ]
            );
        } elseif (!$this->get('mautic.security')->hasEntityAccess(
            'form:forms:viewown',
            'form:forms:viewother',
            $form->getCreatedBy()
        )
        ) {
            return $this->accessDenied();
        }

        if ('POST' == $this->request->getMethod()) {
            $this->setListFilters($this->request->query->get('name'));
        }

        /** @var PageHelperFactoryInterface $pageHelperFacotry */
        $pageHelperFacotry = $this->get('mautic.page.helper.factory');
        $pageHelper        = $pageHelperFacotry->make("mautic.formresult.{$objectId}", $page);

        //set limits
        $limit = $pageHelper->getLimit();
        $start = $pageHelper->getStart();

        // Set order direction to desc if not set
        if (!$session->get('mautic.formresult.'.$objectId.'.orderbydir', null)) {
            $session->set('mautic.formresult.'.$objectId.'.orderbydir', 'DESC');
        }

        $orderBy    = $session->get('mautic.formresult.'.$objectId.'.orderby', 's.date_submitted');
        $orderByDir = $session->get('mautic.formresult.'.$objectId.'.orderbydir', 'DESC');
        $filters    = $session->get('mautic.formresult.'.$objectId.'.filters', []);
        $model      = $this->getModel('form.submission');

        if ($this->request->query->has('result')) {
            // Force ID
            $filters['s.id'] = ['column' => 's.id', 'expr' => 'like', 'value' => (int) $this->request->query->get('result'), 'strict' => false];
            $session->set("mautic.formresult.$objectId.filters", $filters);
        }

        //get the results
        $entities = $model->getEntities(
            [
                'start'          => $start,
                'limit'          => $limit,
                'filter'         => ['force' => $filters],
                'orderBy'        => $orderBy,
                'orderByDir'     => $orderByDir,
                'form'           => $form,
                'withTotalCount' => true,
                'viewOnlyFields' => $viewOnlyFields,
                'simpleResults'  => true,
            ]
        );

        $count   = $entities['count'];
        $results = $entities['results'];
        unset($entities);

        if ($count && $count < ($start + 1)) {
            //the number of entities are now less then the current page so redirect to the last page
            $lastPage = $pageHelper->countPage($count);
            $pageHelper->rememberPage($lastPage);
            $returnUrl = $this->generateUrl('mautic_form_results', ['objectId' => $objectId, 'page' => $lastPage]);

            return $this->postActionRedirect(
                [
                    'returnUrl'       => $returnUrl,
                    'viewParameters'  => ['page' => $lastPage],
                    'contentTemplate' => 'Mautic\FormBundle\Controller\ResultController::indexAction',
                    'passthroughVars' => [
                        'activeLink'    => 'mautic_form_index',
                        'mauticContent' => 'formresult',
                    ],
                ]
            );
        }

        //set what page currently on so that we can return here if need be
        $pageHelper->rememberPage($page);

        return $this->delegateView(
            [
                'viewParameters' => [
                    'items'          => $results,
                    'filters'        => $filters,
                    'form'           => $form,
                    'viewOnlyFields' => $viewOnlyFields,
                    'page'           => $page,
                    'totalCount'     => $count,
                    'limit'          => $limit,
                    'tmpl'           => $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'index') : 'index',
                    'canDelete'      => $this->get('mautic.security')->hasEntityAccess(
                        'form:forms:editown',
                        'form:forms:editother',
                        $form->getCreatedBy()
                    ),
                ],
                'contentTemplate' => 'MauticFormBundle:Result:list.html.twig',
                'passthroughVars' => [
                    'activeLink'    => 'mautic_form_index',
                    'mauticContent' => 'formresult',
                    'route'         => $this->generateUrl(
                        'mautic_form_results',
                        [
                            'objectId' => $objectId,
                            'page'     => $page,
                        ]
                    ),
                ],
            ]
        );
    }

    /**
     * @param int    $submissionId
     * @param string $field
     *
     * @return BinaryFileResponse
     */
    public function downloadFileAction($submissionId, $field)
    {
        /** @var SubmissionResultLoader $submissionResultLoader */
        $submissionResultLoader = $this->getModel('form.submission_result_loader');
        $submission             = $submissionResultLoader->getSubmissionWithResult($submissionId);

        if (!$submission) {
            throw $this->createNotFoundException();
        }

        $results     = $submission->getResults();
        $fieldEntity = $submission->getFieldByAlias($field);

        if (empty($results[$field]) || null === $fieldEntity) {
            throw $this->createNotFoundException();
        }

        if (empty($fieldEntity->getProperties()['public']) && !$this->get('mautic.security')->hasEntityAccess(
            'form:forms:viewown',
            'form:forms:viewother',
            $submission->getForm()->getCreatedBy())
        ) {
            return $this->accessDenied();
        }

        /** @var FormUploader $formUploader */
        $formUploader = $this->get('mautic.form.helper.form_uploader');

        $fileName = $results[$field];
        $file     = $formUploader->getCompleteFilePath($fieldEntity, $fileName);

        $fs = new Filesystem();
        if (!$fs->exists($file)) {
            throw $this->createNotFoundException();
        }

        $response = new BinaryFileResponse($file);
        $response::trustXSendfileTypeHeader();
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $fileName
        );

        return $response;
    }

    /**
     * @param int    $objectId
     * @param string $format
     *
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     *
     * @throws \Exception
     */
    public function exportAction($objectId, $format = 'csv')
    {
        $formModel = $this->getModel('form.form');
        $form      = $formModel->getEntity($objectId);
        $session   = $this->get('session');
        $formPage  = $session->get('mautic.form.page', 1);
        $returnUrl = $this->generateUrl('mautic_form_index', ['page' => $formPage]);

        if (null === $form) {
            //redirect back to form list
            return $this->postActionRedirect(
                [
                    'returnUrl'       => $returnUrl,
                    'viewParameters'  => ['page' => $formPage],
                    'contentTemplate' => 'Mautic\FormBundle\Controller\FormController::indexAction',
                    'passthroughVars' => [
                        'activeLink'    => 'mautic_form_index',
                        'mauticContent' => 'form',
                    ],
                    'flashes' => [
                        [
                            'type'    => 'error',
                            'msg'     => 'mautic.form.error.notfound',
                            'msgVars' => ['%id%' => $objectId],
                        ],
                    ],
                ]
            );
        } elseif (!$this->get('mautic.security')->hasEntityAccess(
            'form:forms:viewown',
            'form:forms:viewother',
            $form->getCreatedBy()
        )
        ) {
            return $this->accessDenied();
        }

        $orderBy    = $session->get('mautic.formresult.'.$objectId.'.orderby', 's.date_submitted');
        $orderByDir = $session->get('mautic.formresult.'.$objectId.'.orderbydir', 'DESC');
        $filters    = $session->get('mautic.formresult.'.$objectId.'.filters', []);

        $args = [
            'limit'      => false,
            'filter'     => ['force' => $filters],
            'orderBy'    => $orderBy,
            'orderByDir' => $orderByDir,
            'form'       => $form,
        ];

        /** @var \Mautic\FormBundle\Model\SubmissionModel $model */
        $model = $this->getModel('form.submission');

        return $model->exportResults($format, $form, $args);
    }

    /**
     * Delete a form result.
     *
     * @return array|\Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteAction()
    {
        $formId   = $this->request->get('formId', 0);
        $objectId = $this->request->get('objectId', 0);
        $session  = $this->get('session');
        $page     = $session->get("mautic.formresult.{$formId}.page", 1);
        $flashes  = [];

        if (Request::METHOD_POST === $this->request->getMethod()) {
            $model = $this->getModel('form.submission');
            \assert($model instanceof SubmissionModel);

            // Find the result
            $entity = $model->getEntity($objectId);

            if (null === $entity) {
                $flashes[] = [
                    'type'    => 'error',
                    'msg'     => 'mautic.form.error.notfound',
                    'msgVars' => ['%id%' => $objectId],
                ];
            } elseif (!$this->get('mautic.security')->hasEntityAccess('form:forms:editown', 'form:forms:editother', $entity->getCreatedBy())) {
                return $this->accessDenied();
            } else {
                $id = $entity->getId();
                $model->deleteEntity($entity);

                $flashes[] = [
                    'type'    => 'notice',
                    'msg'     => 'mautic.core.notice.deleted',
                    'msgVars' => [
                        '%name%' => '#'.$id,
                    ],
                ];
            }
        } //else don't do anything

        $viewParameters = [
            'objectId' => $formId,
            'page'     => $page,
        ];

        return $this->postActionRedirect(
            [
                'returnUrl'       => $this->generateUrl('mautic_form_results', $viewParameters),
                'viewParameters'  => $viewParameters,
                'contentTemplate' => 'Mautic\FormBundle\Controller\ResultController::indexAction',
                'passthroughVars' => [
                    'mauticContent' => 'formresult',
                ],
                'flashes' => $flashes,
            ]
        );
    }

    /**
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function batchDeleteAction()
    {
        return $this->batchDeleteStandard();
    }

    /**
     * @return string
     */
    protected function getModelName()
    {
        return 'form.submission';
    }

    /**
     * @return string
     */
    protected function getIndexRoute()
    {
        return 'mautic_form_results';
    }

    /**
     * @return string
     */
    protected function getActionRoute()
    {
        return 'mautic_form_results_action';
    }

    /**
     * Set the main form ID as the objectId.
     */
    protected function generateUrl(string $route, array $parameters = [], int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH): string
    {
        $formId = $this->getFormIdFromRequest($parameters);
        switch ($route) {
            case 'mautic_form_results_action':
                $parameters['formId'] = $formId;
                break;
            case 'mautic_form_results':
                $parameters['objectId'] = $formId;
                break;
        }

        return parent::generateUrl($route, $parameters, $referenceType);
    }

    /**
     * @param $action
     */
    public function getPostActionRedirectArguments(array $args, $action)
    {
        switch ($action) {
            case 'batchDelete':
                $formId                             = $this->getFormIdFromRequest();
                $args['viewParameters']['objectId'] = $formId;
                break;
        }

        return $args;
    }

    /**
     * @param array $parameters
     *
     * @return mixed
     */
    protected function getFormIdFromRequest($parameters = [])
    {
        if ($this->request->attributes->has('formId')) {
            $formId = $this->request->attributes->get('formId');
        } elseif ($this->request->request->has('formId')) {
            $formId = $this->request->request->get('formId');
        } else {
            $objectId = isset($parameters['objectId']) ? $parameters['objectId'] : 0;
            $formId   = (isset($parameters['formId'])) ? $parameters['formId'] : $this->request->query->get('formId', $objectId);
        }

        return $formId;
    }
}
