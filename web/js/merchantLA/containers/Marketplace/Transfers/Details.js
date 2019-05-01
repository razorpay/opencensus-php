import React, { Component } from 'react';
import { connect } from 'react-redux';
import { findDOMNode } from 'react-dom';
import { withRouter } from 'react-router-dom';
import TransferDetails from 'merchantLA/components/Marketplace/Transfers/Details';
import ReversalDetails from 'merchantLA/containers/Marketplace/Reversals/Details.js';
import {
  fetchTransfer,
  fetchReversals,
} from 'merchantLA/modules/marketplace/transfer';
import * as ModalActions from 'rzp/modules/modals';
import { expandSlider, compactSlider } from 'rzp/modules/slider';
import { showNotification } from 'rzp/modules/notifications';
import setGaTrack from './ga';

const gaEvents = setGaTrack('LA Dashboard - Transfers');

@withRouter
@connect(state => ({ ...state.transfer, ...state.session }), {
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

  onReversalDetailsClose = () => {
    let { compactSlider, history, location } = this.props;

    if (this.reversalsView) {
      findDOMNode(this.reversalsView).classList.toggle('toggle-slider');
    }

    compactSlider();

    // Going back to initial detail view mode
    history.push(location.pathname.replace(/\/[^\/]+\/?$/, ''));
  };

  componentDidMount() {
    gaEvents.trackOpenDetails();
  }

  componentWillUnmount() {
    gaEvents.trackCloseDetails();
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
      user,
      reversal_id,
    } = this.props;
    let statusMsg = {};

    if (errors) {
      statusMsg = {
        type: 'error',
        message: errors,
      };
    }

    return (
      <div
        class={`transfer-details-container ${
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
          showNotification={showNotification}
          parentAccountName={user.marketplace_merchant_name}
          showRefundToCustomer={user.features.includes(
            'allow_reversals_from_la'
          )}
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
