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

  componentWillMount() {
    this.props.fetchTransfer(this.props.id);
    this.props.fetchReversals(this.props.id);
  }

  // Open modal for reversing transfer
  openReversalModal = transfer => {
    this.props.openModal({
      component: <ReversalModal transfer={transfer} />,
    });
  };

  // Fetch reversals list in details view
  fetchReversals = id => {
    return this.props.fetchReversals(id);
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
        onToggleReversalsList={this.fetchReversals}
      />
    );
  }
}
