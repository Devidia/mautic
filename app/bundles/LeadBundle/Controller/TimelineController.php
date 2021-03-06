<?php
/**
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
namespace Mautic\LeadBundle\Controller;

use Mautic\CoreBundle\Controller\CommonController;
use Mautic\CoreBundle\Helper\InputHelper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class TimelineController extends CommonController
{
    use LeadAccessTrait;
    use LeadDetailsTrait;

    public function indexAction(Request $request, $leadId, $page = 1)
    {
        if (empty($leadId)) {
            return $this->accessDenied();
        }

        $lead = $this->checkLeadAccess($leadId, 'view');
        if ($lead instanceof Response) {
            return $lead;
        }

        $this->setListFilters();

        $session = $this->get('session');
        if ($request->getMethod() == 'POST' && $request->request->has('search')) {
            $filters = [
                'search'        => InputHelper::clean($request->request->get('search')),
                'includeEvents' => InputHelper::clean($request->request->get('includeEvents', [])),
                'excludeEvents' => InputHelper::clean($request->request->get('excludeEvents', [])),
            ];
            $session->set('mautic.lead.'.$leadId.'.timeline.filters', $filters);
        } else {
            $filters = null;
        }

        $order = [
            $session->get('mautic.lead.'.$leadId.'.timeline.orderby'),
            $session->get('mautic.lead.'.$leadId.'.timeline.orderbydir'),
        ];

        $events = $this->getEngagements($lead, $filters, $order, $page);

        return $this->delegateView(
            [
                'viewParameters' => [
                    'lead'   => $lead,
                    'page'   => $page,
                    'events' => $events,
                ],
                'passthroughVars' => [
                    'route'         => false,
                    'mauticContent' => 'leadTimeline',
                    'timelineCount' => $events['total'],
                ],
                'contentTemplate' => 'MauticLeadBundle:Timeline:list.html.php',
            ]
        );
    }

    public function pluginIndexAction(Request $request, $integration, $page = 1)
    {
        $limit = 25;
        $leads = $this->checkAllAccess('view', $limit);

        if ($leads instanceof Response) {
            return $leads;
        }

        $this->setListFilters();

        $session = $this->get('session');
        if ($request->getMethod() === 'POST' && $request->request->has('search')) {
            $filters = [
                'search'        => InputHelper::clean($request->request->get('search')),
                'includeEvents' => InputHelper::clean($request->request->get('includeEvents', [])),
                'excludeEvents' => InputHelper::clean($request->request->get('excludeEvents', [])),
            ];
            $session->set('mautic.plugin.timeline.filters', $filters);
        } else {
            $filters = null;
        }

        $order = [
            $session->get('mautic.plugin.timeline.orderby'),
            $session->get('mautic.plugin.timeline.orderbydir'),
        ];

        // get all events grouped by lead
        $events = $this->getAllEngagements($leads, $filters, $order, $page, $limit);

        $str = $this->request->server->get('QUERY_STRING');
        $str = substr($str, strpos($str, '?') + 1);
        parse_str($str, $query);

        return $this->delegateView(
            [
                'viewParameters' => [
                    'leads'       => $leads,
                    'page'        => $page,
                    'events'      => $events,
                    'integration' => $integration,
                    'tmpl'        => (!$this->request->isXmlHttpRequest()) ? 'index' : '',
                    'newCount'    => (array_key_exists('count', $query) && $query['count']) ? $query['count'] : 0,
                ],
                'passthroughVars' => [
                    'route'         => false,
                    'mauticContent' => 'pluginTimeline',
                    'timelineCount' => $events['total'],
                ],
                'contentTemplate' => 'MauticLeadBundle:Timeline:plugin_list.html.php',
            ]
        );
    }

    public function pluginViewAction(Request $request, $integration, $leadId, $page = 1)
    {
        if (empty($leadId)) {
            return $this->notFound();
        }

        $lead = $this->checkLeadAccess($leadId, 'view', true, $integration);
        if ($lead instanceof Response) {
            return $lead;
        }

        $this->setListFilters();

        $session = $this->get('session');
        if ($request->getMethod() === 'POST' && $request->request->has('search')) {
            $filters = [
                'search'        => InputHelper::clean($request->request->get('search')),
                'includeEvents' => InputHelper::clean($request->request->get('includeEvents', [])),
                'excludeEvents' => InputHelper::clean($request->request->get('excludeEvents', [])),
            ];
            $session->set('mautic.plugin.timeline.'.$leadId.'.filters', $filters);
        } else {
            $filters = null;
        }

        $order = [
            $session->get('mautic.plugin.timeline.'.$leadId.'.orderby'),
            $session->get('mautic.plugin.timeline.'.$leadId.'.orderbydir'),
        ];

        $events = $this->getEngagements($lead, $filters, $order, $page);

        return $this->delegateView(
            [
                'viewParameters' => [
                    'lead'        => $lead,
                    'page'        => $page,
                    'integration' => $integration,
                    'events'      => $events,
                ],
                'passthroughVars' => [
                    'route'         => false,
                    'mauticContent' => 'pluginTimeline',
                    'timelineCount' => $events['total'],
                ],
                'contentTemplate' => 'MauticLeadBundle:Timeline:plugin_list.html.php',
            ]
        );
    }
}
