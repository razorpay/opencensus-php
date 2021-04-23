import React from 'react';
import { connect } from 'react-redux';
import RTracking from 'react-tracking';
import HeaderAction from 'common/ui/HeaderAction';
import Alert from 'common/ui/Forms/Alert';
import ListContainer from 'merchant/containers/ListContainer';
import WebhooksList from 'merchant/views/Settings/Webhooks/components/List';
import AddEditWebhook from 'merchant/views/Settings/Webhooks/AddEditWebhook';
import * as WebhookActions from 'merchant/reducers/webhooks';
import * as ModalActions from 'merchant_common/reducers/modals';
import { luminateRow } from 'merchant/reducers/app';
import DocsLink from 'merchant/components/DocsLink';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import CSATSurveyBanner from 'merchant/components/Announcements/CSATSurveyBanner';

@connect(
  (state) => {
    return {
      webhooks: state.webhooks,
      user: state.session.user,
      modeFormatted: state.session.modeFormatted,
    };
  },
  { ...WebhookActions, ...ModalActions, luminateRow },
)
@RTracking(() => window.rzpQ.component('WebhooksContainer'))
export default class WebhooksContainer extends ListContainer {
  fetchEntityList(params) {
    return this.props.fetchWebhooks(params);
  }

  @RTracking(() =>
    window.rzpQ.onbr().initiated('dash.settings_action', {
      action: 'Initiate_Webhook_Setup',
    }),
  )
  showNewWebhookModal = () => {
    const tracking = this.props.tracking;
    const {
      webhooks: { webhooks },
    } = this.props;
    tracking.trackEvent(
      window.rzpQ.merchantActions().initiated('Webhook.setup', {
        webhook_count: webhooks.length,
      }),
    );

    analyticsTrack({
      objectName: 'add webhooks',
      actionName: 'clicked',
      screen: 'settings',
      properties: {
        location: 'webhooks',
        webhookCount: webhooks.length,
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });

    this.props.openModal({
      component: <AddEditWebhook onSave={this.highlightRowAndClose} webhookList={webhooks} />,
    });
  };

  @RTracking(() =>
    window.rzpQ.onbr().initiated('dash.settings_action', {
      action: 'Submit_Webhook_Details',
    }),
  )
  highlightRowAndClose = (webhook) => {
    this.props.luminateRow(webhook.id);
    this.props.closeModal();
  };

  render() {
    const webhooksState = this.props.webhooks;
    const modeFormatted = this.props.modeFormatted;
    const { loadingAllWebhooks, webhooks, error } = webhooksState;

    return (
      <>
        <CSATSurveyBanner user={this.props.user} />
        <div className="content-wrapper" style={{ minHeight: '350px' }}>
          <HeaderAction>
            <div class="btn-toolbar pull-right">
              <DocsLink url="https://razorpay.com/docs/webhooks/" />
              <button className="btn btn-primary" onClick={this.showNewWebhookModal}>
                + Add New Webhook
              </button>
            </div>
          </HeaderAction>

          {error ? <Alert type="error" message={error} /> : null}

          <WebhooksList
            webhooks={webhooks}
            isLoading={loadingAllWebhooks}
            onNewWebhookClick={this.showNewWebhookModal}
            modeFormatted={modeFormatted}
          />
        </div>
      </>
    );
  }
}
