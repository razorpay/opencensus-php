import React, { Component, PropTypes } from 'react';
import { connect } from 'react-redux';
import SubscriptionDetails from 'merchant/components/Subscriptions/Details';
import {
  fetchSubscription as fetchItem,
  cancelSubscription,
} from 'merchant/modules/subscriptions';
import { fetchPlan } from 'merchant/modules/plans';
import { fetchCustomer } from 'merchant/modules/customers';
import { showNotification } from 'rzp/modules/notifications';

@connect(state => state.subscription, {
  fetchItem,
  fetchPlan,
  fetchCustomer,
  cancelSubscription,
  showNotification,
})
export default class SubscriptionDetailsContainer extends Component {
  static contextTypes = {
    confirm: PropTypes.func,
  };

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

  cancelSubscription = () => {
    this.context.confirm({
      header: 'Cancel Subscription?',
      message: "The subscription will be terminated and the customer's card will not be charged.",
      affirmativeLabel: 'Yes',
      abortLabel: 'No',
      action: () =>
        this.props
          .cancelSubscription(this.props.entity.id)
          .then(response => {
            this.props.showNotification({
              type: 'success',
              message: 'Subscription cancelled successfully',
            });
          })
          .catch(({ errors }) => {
            this.props.showNotification({
              type: 'error',
              message: errors,
            });
          }),
    });
  };

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
        onCancelClick={this.cancelSubscription}
      />
    );
  }
}
