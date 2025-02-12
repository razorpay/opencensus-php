import React, { Component } from 'react';
import { connect } from 'react-redux';

import { getEventCategoryFromPath } from 'common/utils/rzp-utils';
import {
  fetchPlan as fetchItem,
  fetchSubscriptionsByPlanId as fetchSubscriptions,
} from 'merchant/reducers/plans';
import PlanDetails from 'merchant/views/Subscriptions/Plans/components/Details';

class PlanDetailsContainer extends Component {
  UNSAFE_componentWillMount() {
    this.props.fetchItem(this.props.id).then(() => {
      this.props.fetchSubscriptions(this.props.entity);
    });
  }

  UNSAFE_componentWillReceiveProps(nextProps) {
    if (this.props.id !== nextProps.id) {
      this.props.fetchItem(nextProps.id).then(() => {
        this.props.fetchSubscriptions(nextProps.entity);
      });
    }
  }

  componentDidMount() {
    const { closeUrl, id } = this.props;
    const eventCategory = getEventCategoryFromPath(closeUrl);
    if (eventCategory) {
      window.rzpAnalytics?.({
        eventCategory,
        eventAction: 'Open Details - Plans',
        eventLabel: `plan_id=${id}`,
      });
    }
  }

  componentWillUnmount() {
    const { closeUrl, id } = this.props;
    const eventCategory = getEventCategoryFromPath(closeUrl);
    if (eventCategory) {
      window.rzpAnalytics?.({
        eventCategory,
        eventAction: 'Close Details - Plans',
        eventLabel: `plan_id=${id}`,
      });
    }
  }

  render() {
    const { loading, error, entity, subscriptions } = this.props;
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
        isLoading={loading}
        statusMsg={statusMsg}
      />
    );
  }
}

export default connect(
  (state) => ({
    ...state.plan,
  }),
  { fetchItem, fetchSubscriptions },
)(PlanDetailsContainer);
