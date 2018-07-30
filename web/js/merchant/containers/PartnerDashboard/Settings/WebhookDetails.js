import { Fragment, Component } from 'react';
import { connect } from 'react-redux';

import { openModal, closeModal } from 'rzp/modules/modals';

import WebhookModal from 'merchant/containers/Webhooks/New';

import Time from 'rzp/ui/Time';
import DetailRow from 'merchant/components/DetailRow';

@connect(
  state => ({
    applicationId: state.applications.partnerApplication.id,
    webhook: state.webhooks.webhooks[0],
  }),
  { openModal, closeModal }
)
export default class WebhookDetails extends Component {
  openWebhookModal = () => {
    this.props.openModal({
      component: (
        <WebhookModal
          webhook={this.props.webhook}
          appId={this.props.applicationId}
        />
      ),
    });
  };
  render() {
    const webhook = this.props.webhook;
    return (
      <div class="list-group details-row-container">
        {webhook ? (
          <Fragment>
            {/* Webhook URL */}
            <DetailRow label="URL" value={() => <code>{webhook.url}</code>} />

            {/* Created At */}
            <DetailRow
              label="Created At"
              value={() => <Time value={webhook.created_at} format="lll" />}
            />

            {/* Status for Web hook */}
            <DetailRow
              label="Status"
              value={!!webhook.active ? 'Active' : 'Inactive'}
            />

            {/* Events enbaled */}
            <DetailRow
              label={`${
                Object.keys(webhook.events).filter(
                  eventType => !!webhook.events[eventType]
                ).length
              } events enabled`}
              value={() => (
                <button onClick={this.openWebhookModal} class="btn btn-link">
                  Edit Webhook
                </button>
              )}
            />
          </Fragment>
        ) : (
          <button onClick={this.openWebhookModal} class="btn btn-default m-t">
            Manage Webhook
          </button>
        )}
      </div>
    );
  }
}
