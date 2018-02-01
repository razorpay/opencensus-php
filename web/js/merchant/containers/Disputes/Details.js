import React, { Component } from 'react';
import { withRouter } from 'react-router-dom';
import { connect } from 'react-redux';
import * as DisputeActions from 'merchant/modules/disputes/details';

import DisputeDetails from 'merchant/components/Disputes/Details';

const findDispute = (disputes, disputeId) =>
  disputes.find(({ id }) => id === disputeId) || disputeId;

@withRouter
@connect(
  state => ({
    disputes: state.disputes.items,
    ...state.dispute,
  }),
  { ...DisputeActions }
)
export default class DisputeDetailsContainer extends Component {
  componentWillMount() {
    this.props.loadDispute(findDispute(this.props.disputes, this.props.id));
  }

  componentWillReceiveProps(nextProps) {
    nextProps.disputes.length &&
      this.props.loadDispute(findDispute(this.props.disputes, nextProps.id));
  }

  render() {
    const { item: dispute, loading, error } = this.props;
    return (
      <div>
        <DisputeDetails isLoading={loading} dispute={dispute} error={error} />
      </div>
    );
  }
}
