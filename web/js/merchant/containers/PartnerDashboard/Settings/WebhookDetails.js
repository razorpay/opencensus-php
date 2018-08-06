import { Component } from 'react';
import { connect } from 'react-redux';

import { openModal, closeModal } from 'rzp/modules/modals';

import ManageWebhook from './ManageWebhook';

import DetailRow from 'merchant/components/DetailRow';

@connect(
  state => ({
    applicationId: state.applications.partnerApplication.id,
  }),
  { openModal, closeModal }
)
export default class WebhookDetails extends Component {
  openWebhookModal = mode => () => {
    this.props.openModal({
      component: (
        <ManageWebhook
          onSave={this.props.closeModal}
          applicationId={this.props.applicationId}
          mode={mode}
        />
      ),
    });
  };

  render() {
    return (
      <div class="list-group details-row-container partner-settings--webhook-details">
        {/* live webhook */}
        <DetailRow
          label="Live Webhook"
          value={() => (
            <button
              onClick={this.openWebhookModal('live')}
              class="btn btn-link"
            >
              Manage
            </button>
          )}
        />

        {/* test webhook */}
        <DetailRow
          label="Test Webhook"
          value={() => (
            <button
              onClick={this.openWebhookModal('test')}
              class="btn btn-link"
            >
              Manage
            </button>
          )}
        />
      </div>
    );
  }
}
