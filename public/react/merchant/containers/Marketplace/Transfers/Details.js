import React, { Component } from 'react';
import { connect } from 'react-redux';
import TransferDetails from 'merchant/components/Marketplace/Transfers/Details';
import ReversalModal from './ReversalModal';
import {
  fetchTransfer,
  fetchReversals,
} from 'merchant/modules/marketplace/transfer';
import * as ModalActions from 'rzp/modules/modals';

@connect(state => state.transfer, {
  fetchTransfer,
  fetchReversals,
  ...ModalActions,
})
export default class TransferDetailsContainer extends Component {
  state = {};

  fetchData(transferId) {
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

  // Open modal for reversing transfer
  openReversalModal = transfer => {
    this.props.openModal({
      component: <ReversalModal transfer={transfer} />,
    });
  };

  render() {
    let { entity, loading, errors, reversals } = this.props;
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
        openReversalModal={this.openReversalModal}
      />
    );
  }
}
