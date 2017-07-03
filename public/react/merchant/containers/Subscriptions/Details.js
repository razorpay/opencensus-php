import React, { Component } from 'react';
import { connect } from 'react-redux';
import SubscriptionDetails from 'merchant/components/Subscriptions/Details';
import { fetchSubscription as fetchItem } from 'merchant/modules/subscriptions';

@connect(state => state.subscription, { fetchItem })
export default class SubscriptionDetailsContainer extends Component {
  componentWillMount() {
    this.props.fetchItem(this.props.id);
  }

  componentWillReceiveProps(nextProps) {
    if (this.props.id !== nextProps.id) {
      this.props.fetchItem(nextProps.id);
    }
  }

  render() {
    let { loading, error, entity } = this.props;
    let statusMsg = {};
    debugger;

    if (error) {
      statusMsg = {
        type: 'error',
        message: this.props.error,
      };
    }

    return (
      <SubscriptionDetails
        subscription={entity}
        isLoading={loading}
        statusMsg={statusMsg}
      />
    );
  }
}
