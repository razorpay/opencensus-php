import { Component } from 'react';
import Spinner from 'rzp/ui/Spinner';
import WebhookCreation from 'merchant/containers/Webhooks/New';

export default class AppWebhook extends Component {
  render() {
    let component;

    if (this.props.loading) {
      // Edit WebhookCreation won't send appId when it's edit webhook for this application
      return (
        <div class="page-spinner-container">
          <Spinner />
        </div>
      );
    } else {
      return (
        <WebhookCreation
          webhook={this.props.webhook}
          appId={this.props.appId}
          onSave={this.props.onSave}
        />
      );
    }
  }
}
