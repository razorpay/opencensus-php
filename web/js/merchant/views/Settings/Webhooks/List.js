import React from 'react';
import { compose } from 'redux';
import { connect } from 'react-redux';
import RTracking from 'react-tracking';
import { withI18Service } from 'common/i18';
import Alert from 'common/ui/Forms/Alert';
import ListContainer from 'merchant/containers/ListContainer';
import { withRouter } from 'common/deprecated/withRouter';
import WebhooksList from 'merchant/views/Settings/Webhooks/components/List';
import AddEditWebhook from 'merchant/views/Settings/Webhooks/AddEditWebhook';
import * as WebhookActions from 'merchant/reducers/webhooks';
import * as ModalActions from 'merchant_common/reducers/modals';
import { luminateRow } from 'merchant/reducers/app';
import DocsLink from 'merchant/components/DocsLink';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import TriggerOnQueryParamMatch from 'common/ui/TriggerOnQueryParamMatch';
import {
  ADD_NEW_WEBHOOK,
  ACTION_QUERY_PARAM_KEY,
} from 'merchant/views/Settings/deeplink-constants';
import { selfServeTrackInitiate, selfServeTrackSuccess } from 'common/utils/selfServeAnalytics';
import ShowWhen from 'merchant/components/ShowWhen';
import { Modules } from 'common/constant/enums';

class WebhooksContainer extends ListContainer {
  fetchEntityList(params) {
    return this.props.fetchWebhooks(params).then(() => {
      selfServeTrackSuccess({
        selfServeAction: 'Webhook List Fetched',
        page: 'Webhooks',
        screen: this.props.user.isAccountAndSettingsRevampEnabled
          ? Modules.AccountAndSettings
          : 'Settings',
      });
    });
  }

  showNewWebhookModal = () => {
    window.rzpQ.onbr().initiated('dash.settings_action', {
      action: 'Initiate_Webhook_Setup',
    });
    const tracking = this.props.tracking;
    const {
      webhooks: { webhooks },
    } = this.props;
    selfServeTrackInitiate({
      selfServeAction: 'Webhook Added',
      page: 'Webhooks',
      screen: 'Settings',
    });
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
      queryParams: {
        [ACTION_QUERY_PARAM_KEY]: ADD_NEW_WEBHOOK,
      },
    });
  };

  highlightRowAndClose = (webhook) => {
    window.rzpQ.onbr().initiated('dash.settings_action', {
      action: 'Submit_Webhook_Details',
    });
    this.props.luminateRow(webhook.id);
    this.props.closeModal();
    selfServeTrackSuccess({
      selfServeAction: 'Webhook Added',
      page: 'Webhooks',
      screen: 'Settings',
    });
  };

  render() {
    const webhooksState = this.props.webhooks;
    const modeFormatted = this.props.modeFormatted;
    const { loadingAllWebhooks, webhooks, error } = webhooksState;

    return (
      <>
        <TriggerOnQueryParamMatch
          queryParamsMapping={[
            {
              key: ACTION_QUERY_PARAM_KEY,
              value: ADD_NEW_WEBHOOK,
              trigger: this.showNewWebhookModal,
            },
          ]}
        />
        <div>
          <div className="webhooks-cta-section">
            <ShowWhen
              additionalCondition={() =>
                !this.props.i18.isConfigTagEnabled('documentation.documentation')
              }
            >
              <DocsLink url="https://razorpay.com/docs/webhooks/" />
            </ShowWhen>
            <span className="cta-container">
              <button className="btn btn-primary" onClick={this.showNewWebhookModal}>
                + Add New Webhook
              </button>
            </span>
          </div>
          <div className="content-wrapper" style={{ minHeight: '350px' }}>
            {error ? <Alert type="error" message={error} /> : null}

            <WebhooksList
              webhooks={webhooks}
              isLoading={loadingAllWebhooks}
              onNewWebhookClick={this.showNewWebhookModal}
              modeFormatted={modeFormatted}
              skip={this.state.skip}
              count={this.state.count}
              paginate={this.paginate}
            />
          </div>
        </div>
      </>
    );
  }
}

export default compose(
  connect(
    (state) => {
      return {
        webhooks: state.webhooks,
        user: state.session.user,
        modeFormatted: state.session.modeFormatted,
      };
    },
    { ...WebhookActions, ...ModalActions, luminateRow },
  ),
  // eslint-disable-next-line babel/new-cap
  RTracking(() => window.rzpQ.component('WebhooksContainer')),
)(withRouter(withI18Service(WebhooksContainer)));
