import React, { Component } from 'react';
import { connect } from 'react-redux';
import PlanDetails from 'merchant/components/Plans/Details';
import { fetchPlan as fetchItem } from 'merchant/modules/plans';
import { fetchSubscriptionsByPlanId as fetchSubscriptions } from 'merchant/modules/plans';
import { getEventCategoryFromPath } from 'rzp/utils/rzp-utils';

@connect(
  state => ({
    ...state.plan,
  }),
  { fetchItem, fetchSubscriptions }
)
export default class PlanDetailsContainer extends Component {
  componentWillMount() {
    this.props.fetchItem(this.props.id).then(() => {
      this.props.fetchSubscriptions(this.props.entity);
    });
  }

  componentWillReceiveProps(nextProps) {
    if (this.props.id !== nextProps.id) {
      this.props.fetchItem(nextProps.id).then(() => {
        this.props.fetchSubscriptions(nextProps.entity);
      });
    }
  }

  componentDidMount() {
    const { closeUrl, id } = this.props,
      eventCategory = getEventCategoryFromPath(closeUrl);
    eventCategory &&
      window.rzpAnalytics({
        eventCategory: eventCategory,
        eventAction: 'Open Details - Plans',
        eventLabel: `plan_id=${id}`,
      });
  }

  componentWillUnmount() {
    const { closeUrl, id } = this.props,
      eventCategory = getEventCategoryFromPath(closeUrl);
    eventCategory &&
      window.rzpAnalytics({
        eventCategory: eventCategory,
        eventAction: 'Close Details - Plans',
        eventLabel: `plan_id=${id}`,
      });
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
        isLoading={loading}
        statusMsg={statusMsg}
      />
    );
  }
}
