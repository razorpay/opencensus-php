import React, { Component } from 'react';
import { withRouter } from 'react-router-dom';
import { connect } from 'react-redux';
import * as DisputeActions from 'merchant/modules/disputes/details';

import DisputeDetails from 'merchant/components/Disputes/Details';

const findDispute = (disputes, disputeId) =>
  disputes.find(({ id }) => id === disputeId) || {};

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
    this.props.loadDispute(findDispute(this.props.disputes, nextProps.id));
  }

  render() {
    const { item: dispute } = this.props;
    return (
      <div>
        <DisputeDetails dispute={dispute} />
      </div>
    );
  }
}
