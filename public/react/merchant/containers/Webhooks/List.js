import React, { Component } from 'react';
import { connect } from 'react-redux';
import Header from 'rzp/ui/Header';
import Alert from 'rzp/ui/Forms/Alert';
import ListContainer from 'merchant/containers/ListContainer';
import WebhooksList from 'merchant/components/Webhooks/List';
import WebhookCreation from 'merchant/containers/Webhooks/New';
import * as WebhookActions from 'merchant/modules/webhooks';
import * as ModalActions from 'rzp/modules/modals';

@connect(
  state => {
    return {
      webhooks: state.webhooks,
      modeFormatted: state.session.modeFormatted,
    };
  },
  { ...WebhookActions, ...ModalActions }
)
export default class WebhooksContainer extends ListContainer {
  fetchEntityList(params) {
    return this.props.fetchWebhooks(params);
  }

  showWebhookModal = (webhook = null) => {
    this.props.openModal({
      component: (
        <WebhookCreation webhook={webhook} onSave={this.highlightRowAndClose} />
      ),
    });
  };

  highlightRowAndClose = webhook => {
    this.props.highlightWebhookRow(webhook);
    this.props.closeModal();
  };

  render() {
    let webhooksState = this.props.webhooks;
    let modeFormatted = this.props.modeFormatted;
    let { loading, webhooks, error, highlightRowId } = webhooksState;

    return (
      <div class="content-wrapper">
        {error && <Alert type="error" message={error} />}

        <WebhooksList
          webhooks={webhooks}
          isLoading={loading}
          highlightRow={webhook => webhook.id === highlightRowId}
          onSetupWebhookClick={this.showWebhookModal}
          modeFormatted={modeFormatted}
        />
      </div>
    );
  }
}
