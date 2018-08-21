import React, { Component } from 'react';
import { connect } from 'react-redux';
import { fetchReversal } from 'merchant/modules/marketplace/reversal';
import { fetchTransfer } from 'merchant/modules/marketplace/transfer';

import ReversalDetails from 'merchant/components/Marketplace/Reversals/Details';

@connect(state => ({ reversal: state.reversal, transfer: state.transfer }), {
  fetchTransfer,
  fetchReversal,
})
export default class ReversalDetailsContainer extends Component {
  fetchData(reversalId) {
    if (!reversalId) {
      return;
    }

    this.props.fetchReversal(reversalId).then(resp => {
      if (resp) {
        this.props.fetchTransfer(resp.transfer_id);
      }

      return resp;
    });
  }

  componentWillMount() {
    this.fetchData(this.props.id);
  }

  componentWillReceiveProps(nextProps) {
    if (this.props.id !== nextProps.id) {
      this.fetchData(nextProps.id);
    }
  }

  render() {
    const { reversal, transfer, onClose } = this.props;
    let statusMsg = {};

    const errors = reversal.errors || transfer.errors;

    if (errors) {
      statusMsg = {
        type: 'error',
        message: errors,
      };
    }

    return (
      <ReversalDetails
        reversal={reversal.entity}
        transfer={transfer.entity}
        isLoading={reversal.loading || transfer.loading}
        statusMsg={statusMsg}
        onClose={onClose}
      />
    );
  }
}
