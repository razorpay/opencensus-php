import React, { Component } from 'react';
import { connect } from 'react-redux';
import PlanDetails from 'merchant/components/Plans/Details';
import { fetchPlan as fetchItem } from 'merchant/modules/plans';

@connect(state => state.plan, { fetchItem })
export default class PlanDetailsContainer extends Component {
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

    if (error) {
      statusMsg = {
        type: 'error',
        message: error,
      };
    }

    return (
      <PlanDetails plan={entity} isLoading={loading} statusMsg={statusMsg} />
    );
  }
}
