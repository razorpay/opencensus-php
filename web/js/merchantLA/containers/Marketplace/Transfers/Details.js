import React, { Component } from 'react';
import { connect } from 'react-redux';
import { findDOMNode } from 'react-dom';
import { withRouter } from 'react-router-dom';
import ReversalDetails from 'merchantLA/containers/Marketplace/Reversals/Details';
import { fetchTransfer, fetchReversals } from 'merchantLA/reducers/marketplace/transfer';
import * as ModalActions from 'merchant_common/reducers/modals';
import { expandSlider, compactSlider } from 'merchant_common/reducers/slider';
import { showNotification } from 'merchant_common/reducers/notifications';
import setGaTrack from './ga';
import SuspenseWithLoader from '../../../../common/new-ui/SuspenseWithLoader';
import lazy from 'merchant/routes/LazyLoader';
import { compose, bindActionCreators } from 'redux';

const TransferDetails = lazy(() =>
  import(
    /* webpackChunkName: 'TransferDetails' */ 'merchantLA/components/Marketplace/Transfers/Details'
  ),
);

const gaEvents = setGaTrack('LA Dashboard - Transfers');

class TransferDetailsContainer extends Component {
  state = {};

  fetchData(transferId) {
    if (!transferId) {
      return;
    }

    this.props.fetchTransfer(transferId).then(() => this.props.fetchReversals(transferId));
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
      // eslint-disable-next-line react/no-find-dom-node
      if (this.reversalsView && findDOMNode(this.reversalsView)) {
        // eslint-disable-next-line react/no-find-dom-node
        findDOMNode(this.reversalsView).classList.add('toggle-slider');
      }
    } else {
      this.props.expandSlider();

      // To avoid not toggling issue when browser back btn is clicked when secondary view is overlayed in dual view while small-screen
      // eslint-disable-next-line react/no-find-dom-node
      if (this.reversalsView && findDOMNode(this.reversalsView)) {
        // eslint-disable-next-line react/no-find-dom-node
        findDOMNode(this.reversalsView).classList.remove('toggle-slider');
      }
    }
  }

  onReversalDetailsClose = () => {
    const { compactSlider: compactSliderFn, history, location } = this.props;

    if (this.reversalsView) {
      // eslint-disable-next-line react/no-find-dom-node
      findDOMNode(this.reversalsView).classList.toggle('toggle-slider');
    }

    compactSliderFn();

    // Going back to initial detail view mode. Remove the chunk in url after the last /.
    history.push(location.pathname.replace(/\/[^/]+\/?$/, ''));
  };

  componentDidMount() {
    gaEvents.trackOpenDetails();
  }

  componentWillUnmount() {
    gaEvents.trackCloseDetails();
  }

  render() {
    const {
      entity,
      loading,
      errors,
      reversals,
      onClose,
      onReverse,
      showNotification: showNotificationFn,
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
      <div class={`transfer-details-container ${reversal_id ? 'multi-content' : ''}`}>
        <SuspenseWithLoader>
          <TransferDetails
            transfer={entity}
            reversals={reversals}
            isLoading={loading}
            statusMsg={statusMsg}
            onClose={onClose}
            onReverse={onReverse}
            showNotification={showNotificationFn}
            parentAccountName={user.marketplace_merchant_name}
            showRefundToCustomer={user.isAllowedLARefunds}
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

const mapStateToProps = (state) => {
  return { ...state.transfer, ...state.session };
};

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators(
    {
      fetchTransfer,
      fetchReversals,
      showNotification,
      expandSlider,
      compactSlider,
      ...ModalActions,
    },
    dispatch,
  );
};

export default compose(
  withRouter,
  connect(mapStateToProps)(mapDispatchToProps),
)(TransferDetailsContainer);
