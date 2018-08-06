import { Component, Fragment } from 'react';

import { fetchAppWebhooks } from 'merchant/modules/applications';

import CreateWebhook from 'merchant/containers/Webhooks/New';

import Spinner from 'rzp/ui/Spinner';

export default class ManageWebhook extends Component {
  state = {
    loading: true,
  };

  componentWillMount() {
    fetchAppWebhooks(this.props.applicationId, this.props.mode).then(
      response => {
        this.setState({
          webhook: response.data.items[0],
          loading: false,
        });
      }
    );
  }

  render() {
    return (
      <Fragment>
        {this.state.loading ? (
          <div class="page-spinner-container">
            <Spinner />
          </div>
        ) : (
          <CreateWebhook
            webhook={this.state.webhook}
            onSave={this.props.onSave}
            appId={this.props.applicationId}
          />
        )}
      </Fragment>
    );
  }
}
