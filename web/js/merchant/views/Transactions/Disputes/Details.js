import React, { Component } from 'react';
import { withRouter } from 'react-router-dom';
import { connect } from 'react-redux';
import * as DisputeActions from 'merchant/reducers/disputes/details';
import {
  openModal as fnOpenModal,
  closeModal as fnCloseModal,
} from 'merchant_common/reducers/modals';
import { expandSlider, compactSlider } from 'merchant_common/reducers/slider';
import DisputeDetails from 'merchant/views/Transactions/Disputes/components/Details';
// eslint-disable-next-line import/no-cycle
import PaymentDetails from 'merchant/views/Transactions/Payments/Details';
import DualDetailView, { PrimaryView, SecondaryView } from 'common/new-ui/DualDetailView';
import { compose } from 'redux';
import { showNotification } from 'merchant_common/reducers/notifications';

const findDispute = (disputes = [], disputeId) =>
  disputes.find(({ id }) => id === disputeId) || disputeId;

class DisputeDetailsContainer extends Component {
  state = {};

  UNSAFE_componentWillMount() {
    const { isDisputePresentmentEnabled } = this.props.user;

    this.loadDispute(this.props.id);
    if (isDisputePresentmentEnabled && this.props.fileTypes.length === 0) {
      this.props.fetchFileTypes();
    }
  }

  UNSAFE_componentWillReceiveProps(nextProps) {
    if (nextProps.id !== this.props.id) {
      this.loadDispute(nextProps.id);
    }
  }

  loadDispute(disputeId) {
    this.props.loadDispute(findDispute(this.props.disputes, disputeId));
  }

  goToLink = (link) => {
    if (this.props.isOpenedInDualMode) {
      this.props.history.push(`/${link}`);
    }
    // Don't do anything if dual view already opened
    else if (!this.props.entity_name) {
      this.props.history.push(`/disputes/${this.props.id}/${link}`);
    }
  };

  secClose = (close) => {
    const { history, location } = this.props;
    history.push(location.pathname.replace(!close ? /\/[^/]+\/[^/]+\/?$/ : /\/[^/]+\/?$/, ''));
  };

  render() {
    const {
      item: dispute,
      loading,
      error,
      entity_name,
      entity_id,
      openModal,
      closeModal,
      onCloseSecView,
    } = this.props;
    return (
      <DualDetailView secondaryView={entity_name}>
        <PrimaryView>
          <DisputeDetails
            openModal={openModal}
            closeModal={closeModal}
            isLoading={loading}
            dispute={dispute}
            error={error}
            goToLink={this.goToLink}
            onCloseSecView={onCloseSecView}
            showNotification={this.props.showNotification}
          />
        </PrimaryView>
        <SecondaryView entityName="payments">
          <PaymentDetails id={entity_id} onCloseSecView={() => this.secClose(null)} />
        </SecondaryView>
      </DualDetailView>
    );
  }
}

export default compose(
  withRouter,
  connect(
    (state) => ({
      disputes: state.disputes.items,
      ...state.dispute,
      user: state.session.user,
    }),
    {
      ...DisputeActions,
      openModal: fnOpenModal,
      closeModal: fnCloseModal,
      compactSlider,
      expandSlider,
      showNotification,
    },
  ),
)(DisputeDetailsContainer);
