import { Component } from 'react';

import { fetchAppWebhooks } from 'merchant/reducers/applications';

import CreateWebhook from 'merchant/views/Settings/Webhooks/New';

import Spinner from 'common/ui/Spinner';

export default class ManageWebhook extends Component {
  state = {
    loading: true,
  };

  componentWillMount() {
    fetchAppWebhooks(this.props.appId, this.props.mode).then(response => {
      this.setState({
        webhook: response.data.items[0],
        loading: false,
      });
    });
  }

  render() {
    return (
      <>
        {this.state.loading ? (
          <div class="page-spinner-container">
            <Spinner />
          </div>
        ) : (
          <CreateWebhook webhook={this.state.webhook} {...this.props} />
        )}
      </>
    );
  }
}
