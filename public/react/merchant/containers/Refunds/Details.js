import React, { Component } from 'react';
import { connect } from 'react-redux';
import Header from 'rzp/ui/Header';
import RefundDetails from 'merchant/components/Refunds/RefundDetails';
import * as RefundActions from 'merchant/modules/refunds/details';

@connect(state => state.refund, RefundActions)
export default class RefundDetailsContainer extends Component {
  componentWillMount() {
    this.props.fetchRefund(this.props.id);
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
      <div class="react-root">
        <Header title="Refund Detail" />

        <div class="content-wrapper">
          <RefundDetails
            refund={refund}
            isLoading={loading}
            statusMsg={statusMsg}
          />
        </div>
      </div>
    );
  }
}
