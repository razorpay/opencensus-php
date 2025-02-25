import React, { Component } from 'react';

import { fetchAppWebhooks } from 'merchant/reducers/applications';

import AddEditWebhook from 'merchant/views/Settings/Webhooks/AddEditWebhook';

import Spinner from 'common/ui/Spinner';

export default class ManageWebhook extends Component {
  state = {
    loading: true,
  };

  componentDidMount() {
    fetchAppWebhooks(this.props.appId, this.props.mode).then((response) => {
      this.setState({
        webhook: response.data.items[0],
        loading: false,
      });
    });
  }

  render() {
    return (
      <div>
        {this.state.loading ? (
          <div>
            <Spinner />
          </div>
        ) : (
          <AddEditWebhook webhook={this.state.webhook} {...this.props} />
        )}
      </div>
    );
  }
}
