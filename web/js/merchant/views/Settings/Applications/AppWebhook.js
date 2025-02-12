import { Component } from 'react';
import Spinner from 'common/ui/Spinner';
import AddEditWebhook from 'merchant/views/Settings/Webhooks/AddEditWebhook';

export default class AppWebhook extends Component {
  render() {
    if (this.props.loading) {
      // Edit WebhookCreation won't send appId when it's edit webhook for this application
      return (
        <div className="page-spinner-container">
          <Spinner />
        </div>
      );
    } else {
      return (
        <AddEditWebhook
          webhook={this.props.webhook}
          appId={this.props.appId}
          onSave={this.props.onSave}
          mode={this.props.mode}
        />
      );
    }
  }
}
