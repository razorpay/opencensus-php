import React, { Component } from 'react';
import { connect } from 'react-redux';
import Details from 'merchant/views/Settlements/InstantSettlements/InstantSettlementDetails/Details';
import * as InstantSettlementActions from 'merchant/reducers/instantSettlements/details';
import * as ModalActions from 'merchant_common/reducers/modals';
import { getEventCategoryFromPath } from 'common/utils/rzp-utils';
import SettlementBreakupModal from 'merchant/views/Settlements/Settlements/components/Modals/BreakupModal';

@connect((state) => state.instantSettlement, { ...InstantSettlementActions, ...ModalActions })
export default class InstantSettlementDetails extends Component {
  componentWillMount() {
    this.props.fetchItem(this.props.id);
  }

  componentWillReceiveProps(nextProps) {
    if (this.props.id !== nextProps.id) {
      this.props.fetchItem(nextProps.id);
    }
  }

  render() {
    let { loading, error, instantSettlement, closeModal, fetchTotalSettlementAmount } = this.props;
    let statusMsg = {};

    if (error) {
      statusMsg = {
        type: 'error',
        message: error,
      };
    }

    return (
      <Details
        closeModal={closeModal}
        settlement={instantSettlement}
        isLoading={loading}
        statusMsg={statusMsg}
        fetchTotalSettlementAmount={fetchTotalSettlementAmount}
      />
    );
  }
}
