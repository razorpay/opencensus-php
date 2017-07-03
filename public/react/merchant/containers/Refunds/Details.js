import React, { Component } from 'react';
import { connect } from 'react-redux';
import RefundDetails from 'merchant/components/Refunds/RefundDetails';
import * as RefundActions from 'merchant/modules/refunds/details';

@connect(state => state.refund, RefundActions)
export default class RefundDetailsContainer extends Component {
  componentWillMount() {
    this.props.fetchItem(this.props.id);
  }

  componentWillReceiveProps(nextProps) {
    if (this.props.id !== nextProps.id) {
      this.props.fetchItem(nextProps.id);
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
