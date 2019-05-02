import React, { Component } from 'react';
import { connect } from 'react-redux';
import { findDOMNode } from 'react-dom';
import { withRouter } from 'react-router-dom';

import TransferDetails from 'merchant/components/Marketplace/Transfers/Details';
import ReversalDetails from 'merchant/containers/Marketplace/Reversals/Details';
import ReversalModal from './ReversalModal';
import {
  fetchTransfer,
  fetchReversals,
  updateTransfer,
} from 'merchant/modules/marketplace/transfer';
import * as ModalActions from 'rzp/modules/modals';
import { expandSlider, compactSlider } from 'rzp/modules/slider';

import { showNotification } from 'rzp/modules/notifications';

@withRouter
@connect(state => state.transfer, {
  fetchTransfer,
  fetchReversals,
  showNotification,
  expandSlider,
  compactSlider,
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
    this.checkSecView(this.props);
  }

  componentWillReceiveProps(nextProps) {
    if (this.props.id !== nextProps.id) {
      this.fetchData(nextProps.id);
    }

    if (nextProps.reversal_id) {
      this.checkSecView(nextProps);
    }
  }

  checkSecView(props) {
    if (!props.reversal_id) {
      this.props.compactSlider();

      // To avoid not toggling issue when browser back btn is clicked when secondary view is overlayed in dual view while small-screen
      if (this.reversalsView && findDOMNode(this.reversalsView)) {
        findDOMNode(this.reversalsView).classList.add('toggle-slider');
      }
    } else {
      this.props.expandSlider();

      // To avoid not toggling issue when browser back btn is clicked when secondary view is overlayed in dual view while small-screen
      if (this.reversalsView && findDOMNode(this.reversalsView)) {
        findDOMNode(this.reversalsView).classList.remove('toggle-slider');
      }
    }
  }

  onTransferUpdate = patch => {
    return updateTransfer(this.props.entity.id, patch);
  };

  // Open modal for reversing transfer
  openReversalModal = transfer => {
    this.props.openModal({
      component: (
        <ReversalModal transfer={transfer} onReverse={this.props.onReverse} />
      ),
      size: 'small',
    });
  };

  onReversalDetailsClose = () => {
    let { compactSlider, history, location } = this.props;

    if (this.reversalsView) {
      findDOMNode(this.reversalsView).classList.toggle('toggle-slider');
    }

    compactSlider();

    // Going back to initial detail view mode
    history.push(location.pathname.replace(/\/[^\/]+\/?$/, ''));
  };

  render() {
    let {
        entity,
        loading,
        errors,
        reversals,
        onClose,
        onReverse,
        showNotification,
        reversal_id,
      } = this.props,
      statusMsg = {};

    if (errors) {
      statusMsg = {
        type: 'error',
        message: errors,
      };
    }

    return (
      <div
        className={`transfer-details-container ${
          reversal_id ? 'multi-content' : ''
        }`}
      >
        <TransferDetails
          transfer={entity}
          reversals={reversals}
          isLoading={loading}
          statusMsg={statusMsg}
          onClose={onClose}
          onReverse={onReverse}
          openReversalModal={this.openReversalModal}
          onTransferUpdate={this.onTransferUpdate}
          showNotification={showNotification}
        />
        {reversal_id && (
          <ReversalDetails
            id={reversal_id}
            onClose={this.onReversalDetailsClose}
            ref={c => (this.reversalsView = c)}
            notAllowFetchTransfer={true}
          />
        )}
      </div>
    );
  }
}
