import React, { Component } from 'react';
import { connect } from 'react-redux';
import SettlementDetails from 'merchant/views/Settlements/Settlements/components/Details';
import * as SettlementActions from 'merchant/reducers/settlements/details';
import * as ModalActions from 'merchant_common/reducers/modals';
import { getEventCategoryFromPath } from 'common/utils/rzp-utils';
import SettlementBreakupModal from 'merchant/views/Settlements/Settlements/components/Modals/BreakupModal';
import { bindActionCreators } from 'redux';

class SettlementDetailsContainer extends Component {
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
    const { closeUrl, id } = this.props;
    const eventCategory = getEventCategoryFromPath(closeUrl);

    if (eventCategory)
      window.rzpAnalytics?.({
        eventCategory,
        eventAction: 'Open Details - Settlements',
        eventLabel: `settlement_id=${id}`,
      });
  }

  componentWillUnmount() {
    const { closeUrl, id } = this.props;
    const eventCategory = getEventCategoryFromPath(closeUrl);

    if (eventCategory)
      window.rzpAnalytics?.({
        eventCategory,
        eventAction: 'Close Details - Settlements',
        eventLabel: `settlement_id=${id}`,
      });
  }

  render() {
    const { loading, error, settlement, breakupDetails } = this.props;
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

const mapStateToProps = (state) => {
  return state.settlement;
};

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators({ ...SettlementActions, ...ModalActions }, dispatch);
};

export default connect(mapStateToProps, mapDispatchToProps)(SettlementDetailsContainer);
