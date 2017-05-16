import React, { Component } from 'react';
import { connect } from 'react-redux';
import Header from 'rzp/ui/Header';

import { fetchSubscriptions } from 'merchant/modules/subscriptions';
import { fetchPlans } from 'merchant/modules/plans';
import SubscriptionsList
  from 'merchant/components/Subscriptions/SubscriptionsList';

@connect(
  state => {
    let plansState = state.plans;
    let subscriptionsState = state.subscriptions;

    return {
      subscriptions: subscriptionsState.subscriptions,
      plans: plansState.plans,
      loading: subscriptionsState.loading && plansState.loading,
    };
  },
  { fetchSubscriptions, fetchPlans }
)
export default class SubscriptionsListContainer extends Component {
  componentWillMount() {
    this.props.fetchSubscriptions();
    this.props.fetchPlans();
  }

  render() {
    let { loading, subscriptions, plans } = this.props;

    return (
      <div>
        <Header title="Subscriptions">
          <a
            href="#/app/subscriptions/new"
            class="pull-right btn btn-primary btn-rounded"
          >
            <i class="fa fa-plus" />
            <span>New Subscription</span>
          </a>
        </Header>

        <div class="content-wrapper">
          <div class="panel panel-default">
            <SubscriptionsList
              subscriptions={subscriptions}
              plans={plans}
              isLoading={loading}
            />
          </div>
        </div>
      </div>
    );
  }
}
