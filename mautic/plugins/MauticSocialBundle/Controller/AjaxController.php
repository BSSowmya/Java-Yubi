<?php

namespace MauticPlugin\MauticSocialBundle\Controller;

use Mautic\CoreBundle\Controller\AjaxController as CommonAjaxController;
use Mautic\CoreBundle\Helper\InputHelper;
use MauticPlugin\MauticSocialBundle\Model\MonitoringModel;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class AjaxController.
 */
class AjaxController extends CommonAjaxController
{
    /**
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    protected function getNetworkFormAction(Request $request)
    {
        // get the form type
        $type = InputHelper::clean($request->request->get('networkType'));

        // default to empty
        $dataArray = [
            'html'    => '',
            'success' => 0,
        ];

        if (!empty($type)) {
            //get the HTML for the form

            /** @var MonitoringModel $monitoringModel */
            $monitoringModel = $this->get('mautic.social.model.monitoring');
            $formType        = $monitoringModel->getFormByType($type);

            // get the network type form
            $form = $this->get('form.factory')->create($formType, [], ['label' => false, 'csrf_protection' => false]);

            $html = $this->renderView(
                'MauticSocialBundle:FormTheme:'.$type.'_widget.html.twig',
                ['form' => $form->createView()]
            );

            $html = str_replace(
                [
                    $type.'[', // this is going to generate twitter_hashtag[ or twitter_mention[
                    $type.'_', // this is going to generate twitter_hashtag_ or twitter_mention_
                    $type,
                ],
                [
                    'monitoring[properties][',
                    'monitoring_properties_',
                    'monitoring',
                ],
                $html
            );

            $dataArray['html']    = $html;
            $dataArray['success'] = 1;
        }

        return $this->sendJsonResponse($dataArray);
    }
}
