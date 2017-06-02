import React, { Component } from 'react';
import { connect } from 'react-redux';
import TetherComponent from 'react-tether';
import Header from 'rzp/ui/Header';
import Alert from 'rzp/ui/Forms/Alert';
import ListContainer from 'merchant/containers/ListContainer';
import WebhooksList from 'merchant/components/Webhooks/List';
import WebhookCreation from 'merchant/containers/Webhooks/New';
import * as WebhookActions from 'merchant/modules/webhooks';
import * as ModalActions from 'rzp/modules/modals';
import { luminateRow } from 'merchant/modules/app';

@connect(
  state => {
    return {
      webhooks: state.webhooks,
      modeFormatted: state.session.modeFormatted,
    };
  },
  { ...WebhookActions, ...ModalActions, luminateRow }
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
    this.props.luminateRow(webhook.id);
    this.props.closeModal();
  };

  render() {
    let webhooksState = this.props.webhooks;
    let modeFormatted = this.props.modeFormatted;
    let { loading, webhooks, error } = webhooksState;

    return (
      <div class="content-wrapper">
        <TetherComponent
          target="#settings-header"
          attachment="top right"
          targetAttachment="top right"
          offset="-8px 0"
        >
          <div />{/* required by react-tether */}
          <div class="btn-toolbar pull-right">
            <a
              class="btn btn-link"
              href="https://docs.razorpay.com/v1/page/webhooks"
              target="_blank"
            >
              Documentation &nbsp;
              <i class="icon icon-external-link" />
            </a>
          </div>
        </TetherComponent>

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
