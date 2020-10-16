import React, { Component } from 'react';
import { connect } from 'react-redux';
import SettlementDetails from 'merchant/views/Settlements/components/Details';
import * as SettlementActions from 'merchant/reducers/settlements/details';
import * as ModalActions from 'merchant_common/reducers/modals';
import { getEventCategoryFromPath } from 'common/utils/rzp-utils';
import SettlementBreakupModal from 'merchant/views/Settlements/components/Modals/BreakupModal';

@connect((state) => state.settlement, { ...SettlementActions, ...ModalActions })
export default class SettlementDetailsContainer extends Component {
  componentWillMount() {
    this.props.fetchItem(this.props.id);
  }

  componentWillReceiveProps(nextProps) {
    if (this.props.id !== nextProps.id) {
      this.props.fetchItem(nextProps.id);
    }
  }

  fetchBreakupDetails = (settlement) => {
    this.props.openModal({
      component: (
        <SettlementBreakupModal
          settlementId={settlement.id}
          onMount={() => {}}
          onUnmount={() => {}}
        />
      ),
    });
  };

  componentDidMount() {
    const { closeUrl, id } = this.props,
      eventCategory = getEventCategoryFromPath(closeUrl);
    eventCategory &&
      window.rzpAnalytics({
        eventCategory: eventCategory,
        eventAction: 'Open Details - Settlements',
        eventLabel: `settlement_id=${id}`,
      });
  }

  componentWillUnmount() {
    const { closeUrl, id } = this.props,
      eventCategory = getEventCategoryFromPath(closeUrl);
    eventCategory &&
      window.rzpAnalytics({
        eventCategory: eventCategory,
        eventAction: 'Close Details - Settlements',
        eventLabel: `settlement_id=${id}`,
      });
  }

  render() {
    let { loading, error, settlement, breakupDetails } = this.props;
    let statusMsg = {};

    if (error) {
      statusMsg = {
        type: 'error',
        message: this.props.error,
      };
    }

    return (
      <SettlementDetails
        settlement={settlement}
        isLoading={loading}
        statusMsg={statusMsg}
        onToggleBreakupDetails={this.fetchBreakupDetails}
        breakupDetails={breakupDetails}
      />
    );
  }
}
