import React, { Component } from 'react';
import { connect } from 'react-redux';
import { withRouter } from 'react-router-dom';
import RefundDetails from 'merchant/components/Refunds/RefundDetails';
import * as RefundActions from 'merchant/modules/refunds/details';

@withRouter
@connect(state => state.refund, RefundActions)
export default class RefundDetailsContainer extends Component {
  componentWillMount() {
    let id = this.props.id || this.props.match.params.id;
    this.props.fetchRefund(id);
  }

  componentWillReceiveProps(nextProps) {
    let oldId = this.props.id || this.props.match.params.id;
    let newId = nextProps.id || nextProps.match.params.id;
    if (oldId !== newId) {
      this.props.fetchRefund(newId);
    }
  }

  render() {
    let { loading, error, refund, payments } = this.props;
    let statusMsg = {};

    if (error) {
      statusMsg = {
        type: 'error',
        message: this.props.error,
      };
    }

    return (
      <RefundDetails
        refund={refund}
        isLoading={loading}
        statusMsg={statusMsg}
      />
    );
  }
}
