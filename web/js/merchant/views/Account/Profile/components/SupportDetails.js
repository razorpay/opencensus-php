import React, { Component } from 'react';
import { connect } from 'react-redux';
import RTracking from 'react-tracking';
import { fetchSupportDetail } from 'merchant/reducers/support_detail';
import { openModal, closeModal } from 'merchant_common/reducers/modals';
import MerchantDataCollectionModal from 'merchant/views/Settings/SupportDetails/MerchantDataCollectionModal';
import { isMobile } from 'common/utils/validators';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import ShowWhen from 'merchant/components/ShowWhen';
import TextHighlighter from 'common/ui/TextHighlighter';
import { SUPPORT_DETAILS } from '../deeplink-constants';
import { selfServeTrackInitiate } from 'common/utils/selfServeAnalytics';
@connect((state) => ({ support_detail: state.supportdetails.merchantSupportDetail }), {
  fetchSupportDetail,
  openModal,
  closeModal,
})
@RTracking(() => window.rzpQ.component('SupportDetails'))
export default class SupportDetails extends Component {
  componentDidMount() {
    this.props.fetchSupportDetail();
  }

  openAddSupportDetailModal = (data) => {
    const is_edit = Object.keys(this.props.support_detail.data).length;
    if (is_edit) {
      selfServeTrackInitiate({
        selfServeAction: 'Merchant Support Details Updated',
        page: 'Profile',
        screen: 'My Account',
      });
      analyticsTrack({
        objectName: 'support details edit',
        actionName: 'clicked',
        screen: 'my account',
        properties: {
          supportDetailPresent: !!this.props.support_detail.data,
          ...getCommonAnalyticsProperties(window.rzp_user),
        },
      });
    }

    this.props.openModal({
      size: 'small',
      component: (
        <MerchantDataCollectionModal
          closeModal={this.props.closeModal}
          supportModal={true}
          supportDetail={data}
        />
      ),
    });
  };

  render() {
    const { support_detail } = this.props;
    return (
      <div className="panel panel-default">
        <div className="panel-heading">
          <TextHighlighter hashedWith={SUPPORT_DETAILS}>Support Details</TextHighlighter>
          <ShowWhen myRole="owner admin manager">
            <span className="pull-right">
              <a onClick={() => this.openAddSupportDetailModal(support_detail)}>
                {Object.keys(support_detail.data).length ? 'Edit Details' : 'Add Details'}
              </a>
            </span>
          </ShowWhen>
        </div>
        <div className="list-group details-row-container">
          <div className="list-group-item">
            <span>Phone number</span>
            {support_detail.data.phone ? (
              <span>
                {isMobile(support_detail.data.phone) ? '+91-' : ''}
                {support_detail.data.phone}
              </span>
            ) : (
              <span>--</span>
            )}
          </div>

          <div className="list-group-item">
            <span>Email id</span>
            {support_detail.data.email ? <span>{support_detail.data.email}</span> : <span>--</span>}
          </div>
          <div className="list-group-item">
            <span>Website/Contact us link</span>
            {support_detail.data.url ? <span>{support_detail.data.url}</span> : <span>--</span>}
          </div>
        </div>
      </div>
    );
  }
}
