import React, { Component } from 'react';
import { connect } from 'react-redux';
import rTracking from 'react-tracking';
import { fetchSupportDetail } from 'merchant/reducers/support_detail';
import { openModal, closeModal } from 'merchant_common/reducers/modals';
import MerchantDataCollectionModal from 'merchant/views/Settings/SupportDetails/MerchantDataCollectionModal';
import { isMobile } from 'common/utils/validators';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import ShowWhen from 'merchant/components/ShowWhen';
import TextHighlighter from 'common/ui/TextHighlighter';
import {
  SUPPORT_DETAILS,
  ACTION_QUERY_PARAM_KEY,
  UPDATE_SUPPORT_DETAILS,
} from 'merchant/views/Account/Profile/deeplink-constants';
import TriggerOnQueryParamMatch from 'common/ui/TriggerOnQueryParamMatch';
import { compose } from 'redux';

class SupportDetails extends Component {
  componentDidMount() {
    this.props.fetchSupportDetail();
  }

  openAddSupportDetailModal = (data) => {
    const is_edit = Object.keys(this.props.support_detail.data).length;
    if (is_edit) {
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
      queryParams: {
        [ACTION_QUERY_PARAM_KEY]: UPDATE_SUPPORT_DETAILS,
      },
    });
  };

  render() {
    const { support_detail } = this.props;

    const handleOpenAddSupportDetailModal = () => this.openAddSupportDetailModal(support_detail);

    return (
      <TriggerOnQueryParamMatch
        queryParamsMapping={[
          {
            key: ACTION_QUERY_PARAM_KEY,
            value: UPDATE_SUPPORT_DETAILS,
            trigger: handleOpenAddSupportDetailModal,
          },
        ]}
      >
        <div className="panel panel-default">
          <div className="panel-heading">
            <TextHighlighter hashedWith={SUPPORT_DETAILS}>Support Details</TextHighlighter>
            <ShowWhen myRole="owner admin manager">
              <span className="pull-right">
                <a onClick={handleOpenAddSupportDetailModal}>
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
              {support_detail.data.email ? (
                <span>{support_detail.data.email}</span>
              ) : (
                <span>--</span>
              )}
            </div>
            <div className="list-group-item">
              <span>Website/Contact us link</span>
              {support_detail.data.url ? <span>{support_detail.data.url}</span> : <span>--</span>}
            </div>
          </div>
        </div>
      </TriggerOnQueryParamMatch>
    );
  }
}

export default compose(
  rTracking({
    page: 'SupportDetails',
  }),
  connect((state) => ({ support_detail: state.supportdetails.merchantSupportDetail }), {
    fetchSupportDetail,
    openModal,
    closeModal,
  }),
)(SupportDetails);
