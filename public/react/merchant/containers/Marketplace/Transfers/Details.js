import React, { Component } from 'react';
import { connect } from 'react-redux';
import TransferDetails from 'merchant/components/Marketplace/Transfers/Details';
import { fetchTransfer } from 'merchant/modules/marketplace/transfer';

@connect(state => state.transfer, {
  fetchTransfer,
})
export default class TransferDetailsContainer extends Component {
  state = {};

  componentWillMount() {
    this.props.fetchTransfer(this.props.id);
  }

  render() {
    let { entity, loading, errors } = this.props;
    let statusMsg = {};

    if (errors) {
      statusMsg = {
        type: 'error',
        message: errors,
      };
    }

    return (
      <TransferDetails
        transfer={entity}
        isLoading={loading}
        statusMsg={statusMsg}
      />
    );
  }
}
