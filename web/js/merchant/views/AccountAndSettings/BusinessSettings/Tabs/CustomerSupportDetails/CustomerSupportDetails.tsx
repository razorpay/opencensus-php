import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { fetchSupportDetail as fetchSupportDetailFn } from 'merchant/reducers/support_detail';
import DetailsViewCard from 'merchant/views/AccountAndSettings/BusinessSettings/components/DetailsViewCard';
import DetailsViewShimmer from 'merchant/views/AccountAndSettings/BusinessSettings/components/DetailsViewCard/Shimmer';
import { CustomerSupportDetailProps } from 'merchant/views/AccountAndSettings/BusinessSettings/typings';
import MerchantDataCollectionModal from 'merchant/views/Settings/SupportDetails/MerchantDataCollectionModal';
import * as ModalActions from 'merchant_common/reducers/modals';
import React, { useEffect } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators, compose } from 'redux';
import { getInfoData } from './utils';

const CustomerSupportDetails = ({
  fetchSupportDetail,
  openModal,
  closeModal,
  support_detail,
}: CustomerSupportDetailProps): JSX.Element => {
  useEffect(() => {
    fetchSupportDetail();
  }, []);

  const openAddSupportDetailModal = ({ type }: { type?: string }): void => {
    const is_edit = Object.keys(support_detail.data).length;
    if (is_edit) {
      analyticsTrack({
        objectName: 'support details edit',
        actionName: 'clicked',
        screen: 'Account & settings',
        properties: {
          supportDetailPresent: !!support_detail.data,
          version: 'v2',
          editOptionClicked: type,
          ...getCommonAnalyticsProperties(window.rzp_user),
        },
      });
    }
    openModal({
      size: 'small',
      component: (
        <MerchantDataCollectionModal
          closeModal={closeModal}
          supportModal={true}
          supportDetail={support_detail}
          isIndividual={true}
          editField={type}
        />
      ),
    });
  };

  if (support_detail.loading) {
    return <DetailsViewShimmer title="Customer Support Details" />;
  }

  const info = getInfoData({ support_detail });

  return (
    <DetailsViewCard
      title="Customer Support Details"
      info={info}
      handleAction={openAddSupportDetailModal}
    />
  );
};

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators(
    { fetchSupportDetail: fetchSupportDetailFn, ...ModalActions },
    dispatch,
  );
};

export default compose<any>(
  connect(
    (state): { support_detail: Pick<CustomerSupportDetailProps, 'support_detail'> } => ({
      support_detail: state.supportdetails.merchantSupportDetail,
    }),
    mapDispatchToProps,
  ),
)(CustomerSupportDetails);
