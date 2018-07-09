import React, { Component } from 'react';
import { connect } from 'react-redux';
import TransferDetails from 'merchantLA/components/Marketplace/Transfers/Details';
import {
  fetchTransfer,
  fetchReversals,
} from 'merchant/modules/marketplace/transfer';
import * as ModalActions from 'rzp/modules/modals';

import { showNotification } from 'rzp/modules/notifications';

@connect(state => state.transfer, {
  fetchTransfer,
  fetchReversals,
  showNotification,
  ...ModalActions,
})
export default class TransferDetailsContainer extends Component {
  state = {};

  fetchData(transferId) {
    if (!transferId) {
      return;
    }

    this.props
      .fetchTransfer(transferId)
      .then(() => this.props.fetchReversals(transferId));
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
    let {
      entity,
      loading,
      errors,
      reversals,
      onClose,
      onReverse,
      showNotification,
    } = this.props;
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
        reversals={reversals}
        isLoading={loading}
        statusMsg={statusMsg}
        onClose={onClose}
        onReverse={onReverse}
        showNotification={showNotification}
      />
    );
  }
}
