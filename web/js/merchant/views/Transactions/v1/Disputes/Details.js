import React, { Component } from 'react';
import { withRouter } from 'common/deprecated/withRouter';
import { connect } from 'react-redux';
import { compose } from 'redux';

import { withSplitzService } from 'common/splitz';
import { selfServeTrackSuccess } from 'common/utils/selfServeAnalytics';
import * as DisputeActions from 'merchant/reducers/disputes/details';
import DisputeDetails from 'merchant/views/Transactions/v1/Disputes/components/Details';
// eslint-disable-next-line import/no-cycle
import { getSelfServeSuccessData } from 'merchant/views/Transactions/v1/utils';
import {
  openModal as fnOpenModal,
  closeModal as fnCloseModal,
} from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';
import { expandSlider, compactSlider } from 'merchant_common/reducers/slider';

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
    const { splitz } = this.props;
    this.props.loadDispute(findDispute(this.props.disputes, disputeId)).then((response) => {
      const selfServeSuccessData = getSelfServeSuccessData(
        'Dispute Details Fetched',
        'Dispute Details',
        splitz,
      );
      selfServeTrackSuccess(selfServeSuccessData);
      return response;
    });
  }

  render() {
    const { item: dispute, loading, error, openModal, closeModal, onCloseSecView } = this.props;
    return (
      <DisputeDetails
        openModal={openModal}
        closeModal={closeModal}
        isLoading={loading}
        dispute={dispute}
        error={error}
        onCloseSecView={onCloseSecView}
        showNotification={this.props.showNotification}
      />
    );
  }
}

export default compose(
  withSplitzService,
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
