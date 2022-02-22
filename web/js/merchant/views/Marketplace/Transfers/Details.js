/* eslint-disable react/no-find-dom-node */
import React, { Component } from 'react';
import { connect } from 'react-redux';
import { findDOMNode } from 'react-dom';
import { withRouter } from 'react-router-dom';

import ReversalDetails from 'merchant/views/Marketplace/Reversals/Details';
import ReversalModal from './ReversalModal';
import {
  fetchTransfer,
  fetchReversals,
  updateTransfer,
} from 'merchant/reducers/marketplace/transfers/details';
import * as ModalActions from 'merchant_common/reducers/modals';
import { expandSlider, compactSlider } from 'merchant_common/reducers/slider';

import { showNotification } from 'merchant_common/reducers/notifications';
import lazy from 'merchant/routes/LazyLoader';
import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';

const TransferDetails = lazy(() =>
  import(
    /* webpackChunkName: 'TransferDetails' */ 'merchant/views/Marketplace/Transfers/components/Details'
  ),
);

@withRouter
@connect((state) => state.transfer, {
  fetchTransfer,
  fetchReversals,
  showNotification,
  expandSlider,
  compactSlider,
  updateTransfer,
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
      .then(() => this.props.fetchReversals(transferId))
      .catch((error) => {
        this.props.showNotification({
          type: 'error',
          message: error.errors?.[0] || 'Failed to connect to the server',
        });
      });
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

  onTransferUpdate = (patch) => {
    return this.props.updateTransfer(this.props.entity.id, patch);
  };

  // Open modal for reversing transfer
  openReversalModal = (transfer) => {
    this.props.openModal({
      component: <ReversalModal transfer={transfer} onReverse={this.props.onReverse} />,
      size: 'small',
    });
  };

  onReversalDetailsClose = () => {
    // eslint-disable-next-line no-shadow
    const { compactSlider, history, location } = this.props;

    if (this.reversalsView) {
      findDOMNode(this.reversalsView).classList.toggle('toggle-slider');
    }

    compactSlider();

    // Going back to initial detail view mode. Remove the chunk in url after the last /.
    // eslint-disable-next-line no-useless-escape
    history.push(location.pathname.replace(/\/[^\/]+\/?$/, ''));
  };

  render() {
    const {
      entity,
      loading,
      errors,
      reversals,
      onClose,
      onReverse,
      // eslint-disable-next-line no-shadow
      showNotification,
      reversal_id,
      isDirectTransferEnabled,
    } = this.props;
    let statusMsg = {};

    if (errors) {
      statusMsg = {
        type: 'error',
        message: errors,
      };
    }

    return (
      <div class={`transfer-details-container ${reversal_id ? 'multi-content' : ''}`}>
        <SuspenseWithLoader>
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
            isDirectTransferEnabled={isDirectTransferEnabled}
          />
        </SuspenseWithLoader>
        {reversal_id && (
          <ReversalDetails
            id={reversal_id}
            onClose={this.onReversalDetailsClose}
            ref={(c) => (this.reversalsView = c)}
            notAllowFetchTransfer={true}
          />
        )}
      </div>
    );
  }
}
