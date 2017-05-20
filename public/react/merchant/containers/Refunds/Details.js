import React, { Component } from 'react';
import { connect } from 'react-redux';
import RefundDetails from 'merchant/components/Refunds/RefundDetails';
import * as RefundActions from 'merchant/modules/refunds/details';
import * as SliderActions from 'rzp/modules/slider';

@connect(state => state.refund, { ...RefundActions, ...SliderActions })
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

  closeSlider = () => {
    this.props.closeSlider({
      closeURL: '/app/refunds',
    });
  };

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
        onCloseClick={this.closeSlider}
      />
    );
  }
}
