import React, { Component } from 'react';
import { connect } from 'react-redux';
import PlanDetails from 'merchant/components/Plans/Details';
import { fetchPlan as fetchItem } from 'merchant/modules/plans';
import { fetchSubscriptions } from 'merchant/modules/subscriptions';

@connect(
  state => ({
    ...state.plan,
    subscriptions: state.subscriptions,
  }),
  { fetchItem, fetchSubscriptions }
)
export default class PlanDetailsContainer extends Component {
  componentWillMount() {
    this.props.fetchItem(this.props.id);
  }

  fetchSubscriptionsByPlanId = planId => {
    return this.props.fetchSubscriptions({
      plan_id: planId,
    });
  };

  componentWillReceiveProps(nextProps) {
    if (this.props.id !== nextProps.id) {
      this.props.fetchItem(nextProps.id);
    }
  }

  render() {
    let { loading, error, entity, subscriptions } = this.props;
    let statusMsg = {};

    if (error) {
      statusMsg = {
        type: 'error',
        message: error,
      };
    }

    return (
      <PlanDetails
        plan={entity}
        subscriptions={subscriptions}
        onToggleSusbsList={this.fetchSubscriptionsByPlanId}
        isLoading={loading}
        statusMsg={statusMsg}
      />
    );
  }
}
