import React, { Component } from 'react';
import { connect } from 'react-redux';
import SubscriptionDetails from 'merchant/components/Subscriptions/Details';
import { fetchSubscription as fetchItem } from 'merchant/modules/subscriptions';
import { fetchPlan } from 'merchant/modules/plans';
import { fetchCustomer } from 'merchant/modules/customers';

@connect(state => state.subscription, { fetchItem, fetchPlan, fetchCustomer })
export default class SubscriptionDetailsContainer extends Component {
  state = {};

  componentWillMount() {
    this.fetchSubscriptionDetails(this.props.id);
  }

  componentWillReceiveProps(nextProps) {
    if (this.props.id !== nextProps.id) {
      this.props.fetchItem(nextProps.id);
    }
  }

  fetchSubscriptionDetails(id) {
    this.setState({ isLoading: true });
    this.props
      .fetchItem(id)
      .then(subscription => {
        return Promise.all([
          this.props.fetchPlan(subscription.plan_id),
          this.props.fetchCustomer(subscription.customer_id),
        ]);
      })
      .then(() => {
        this.setState({ isLoading: false });
      });
  }

  render() {
    let { error, entity, plan, customer } = this.props;
    let isLoading = this.state.isLoading;
    let statusMsg = {};

    if (error) {
      statusMsg = {
        type: 'error',
        message: this.props.error,
      };
    }

    return (
      <SubscriptionDetails
        subscription={entity}
        plan={plan}
        customer={customer}
        isLoading={isLoading}
        statusMsg={statusMsg}
      />
    );
  }
}
