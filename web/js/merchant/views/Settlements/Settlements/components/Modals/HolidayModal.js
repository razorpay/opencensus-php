import { Component } from 'react';
import { connect } from 'react-redux';
import ModalHeader from 'common/ui/ModalHeader';
import HolidaysTable from 'merchant/views/Settlements/Settlements/components/HolidayTable';
import { fetchBreakupDetails } from 'merchant/reducers/settlements/details';
import * as ModalActions from 'merchant_common/reducers/modals';
import { bindActionCreators } from 'redux';
import React from "react";

class HolidayModal extends Component {
  render() {
    const date = new Date();
    const year = date.getUTCFullYear().toString();
    return (
      <div>
        <ModalHeader
          title="Holidays List"
          onCloseClick={() => {
            this.props.closeModal();
            window.rzpAnalytics?.({
              eventCategory: 'Settlement Revamp',
              eventAction: 'Close',
              eventLabel: `List of Bank Holidays`,
            });
          }}
        />
        <HolidaysTable items={this.props.holidayList?.data?.[year] || []} loading={false} />
      </div>
    );
  }
}

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators({ fetchBreakupDetails, ...ModalActions }, dispatch);
};

export default connect(null, mapDispatchToProps)(HolidayModal);
