import React, { Component } from 'react';
import { connect } from 'react-redux';
import Header from 'rzp/ui/Header';

import { fetchSubscriptions } from 'rzp/modules/collection';
import { fetchPlans } from 'merchant/modules/plans';
import SubscriptionsList
  from 'merchant/components/Subscriptions/SubscriptionsList';

@connect(
  state => {
    let plansState = state.plans;
    let subscriptionsState = state.subscriptions;

    return {
      items: subscriptionsState.items,
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
    let { loading, items, plans } = this.props;

    return (
      <div>
        <Header title="Subscriptions">
          <a href="#/app/subscriptions/new" class="pull-right btn btn-primary">
            <i className="icon icon-plus" />
            <span>New Subscription</span>
          </a>
        </Header>

        <div class="content-wrapper">
          <div class="panel panel-default">
            <SubscriptionsList
              subscriptions={items}
              plans={plans}
              isLoading={loading}
            />
          </div>
        </div>
      </div>
    );
  }
}
