import React, { Component } from 'react';
import { connect } from 'react-redux';
import RTracking from 'react-tracking';
import HeaderAction from 'rzp/ui/HeaderAction';
import Alert from 'rzp/ui/Forms/Alert';
import ListContainer from 'merchant/containers/ListContainer';
import WebhooksList from 'merchant/components/Webhooks/List';
import WebhookCreation from 'merchant/containers/Webhooks/New';
import * as WebhookActions from 'merchant/reducers/webhooks';
import * as ModalActions from 'merchant_common/reducers/modals';
import { luminateRow } from 'merchant/reducers/app';
import DocsLink from 'merchant/components/DocsLink';

@connect(
  state => {
    return {
      webhooks: state.webhooks,
      modeFormatted: state.session.modeFormatted,
    };
  },
  { ...WebhookActions, ...ModalActions, luminateRow }
)
@RTracking(() => window.rzpQ.component('WebhooksContainer'))
export default class WebhooksContainer extends ListContainer {
  fetchEntityList(params) {
    return this.props.fetchWebhooks(params);
  }

  @RTracking(() =>
    window.rzpQ.onbr().initiated('dash.settings_action', {
      action: 'Initiate_Webhook_Setup',
    })
  )
  showWebhookModal = (webhook = null) => {
    this.props.openModal({
      component: (
        <WebhookCreation webhook={webhook} onSave={this.highlightRowAndClose} />
      ),
    });
  };

  @RTracking(() =>
    window.rzpQ.onbr().initiated('dash.settings_action', {
      action: 'Submit_Webhook_Details',
    })
  )
  highlightRowAndClose = webhook => {
    this.props.luminateRow(webhook.id);
    this.props.closeModal();
  };

  render() {
    let webhooksState = this.props.webhooks;
    let modeFormatted = this.props.modeFormatted;
    let { loading, webhooks, error } = webhooksState;

    return (
      <div class="content-wrapper">
        <HeaderAction>
          <div class="btn-toolbar pull-right">
            <DocsLink url="https://razorpay.com/docs/webhooks/" />
          </div>
        </HeaderAction>

        {error && <Alert type="error" message={error} />}

        <WebhooksList
          webhooks={webhooks}
          isLoading={loading}
          onSetupWebhookClick={this.showWebhookModal}
          modeFormatted={modeFormatted}
        />
      </div>
    );
  }
}
