<?php
/**
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
namespace Mautic\CampaignBundle;

/**
 * Class CampaignEvents
 * Events available for CampaignBundle.
 */
final class CampaignEvents
{
    /**
     * The mautic.campaign_pre_save event is thrown right before a form is persisted.
     *
     * The event listener receives a
     * Mautic\CampaignBundle\Event\CampaignEvent instance.
     *
     * @var string
     */
    const CAMPAIGN_PRE_SAVE = 'mautic.campaign_pre_save';

    /**
     * The mautic.campaign_post_save event is thrown right after a form is persisted.
     *
     * The event listener receives a
     * Mautic\CampaignBundle\Event\CampaignEvent instance.
     *
     * @var string
     */
    const CAMPAIGN_POST_SAVE = 'mautic.campaign_post_save';

    /**
     * The mautic.campaign_pre_delete event is thrown before a form is deleted.
     *
     * The event listener receives a
     * Mautic\CampaignBundle\Event\CampaignEvent instance.
     *
     * @var string
     */
    const CAMPAIGN_PRE_DELETE = 'mautic.campaign_pre_delete';

    /**
     * The mautic.campaign_post_delete event is thrown after a form is deleted.
     *
     * The event listener receives a
     * Mautic\CampaignBundle\Event\CampaignEvent instance.
     *
     * @var string
     */
    const CAMPAIGN_POST_DELETE = 'mautic.campaign_post_delete';

    /**
     * The mautic.campaign_on_build event is thrown before displaying the campaign builder form to allow adding of custom actions.
     *
     * The event listener receives a
     * Mautic\CampaignBundle\Event\CampaignBuilderEvent instance.
     *
     * @var string
     */
    const CAMPAIGN_ON_BUILD = 'mautic.campaign_on_build';

    /**
     * The mautic.campaign_on_leadchange event is thrown when a lead was added or removed from the campaign.
     *
     * The event listener receives a
     * Mautic\CampaignBundle\Event\CampaignLeadChangeEvent instance.
     *
     * @var string
     */
    const CAMPAIGN_ON_LEADCHANGE = 'mautic.campaign_on_leadchange';

    /**
     * The mautic.campaign_on_event_execution event is dispatched when a campaign event is executed.
     *
     * The event listener receives a
     * Mautic\CampaignBundle\Event\CampaignExecutionEvent instance.
     *
     * @var string
     */
    const ON_EVENT_EXECUTION = 'mautic.campaign_on_event_execution';

    /**
     * The mautic.campaign_on_event_decision_trigger event is dispatched after a lead decision triggers a set of actions or if the decision is set
     * as a root level event.
     *
     * The event listener receives a
     * Mautic\CampaignBundle\Event\CampaignDecisionEvent instance.
     *
     * @var string
     */
    const ON_EVENT_DECISION_TRIGGER = 'matuic.campaign_on_event_decision_trigger';

    /**
     * The mautic.campaign_on_event_scheduled event is dispatched when a campaign event is scheduled.
     *
     * The event listener receives a
     * Mautic\CampaignBundle\Event\CampaignScheduledEvent instance.
     *
     * @var string
     */
    const ON_EVENT_SCHEDULED = 'matuic.campaign_on_event_scheduled';
}
