import React, { ReactNode, useState } from 'react';
import { Button } from '@razorpay/blade/components';
import moment from 'moment';
import { connect } from 'react-redux';
import { useNavigate } from 'react-router-dom';
import { bindActionCreators, compose } from 'redux';

import { ShowNotificationType, User } from 'common/typings';
import { isMobileAndTablet } from 'common/utils/rzp-utils';
import { PGAcceptedInviteItem } from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/ProductClientAccounts/common/api';
import { getIsInviteFlowEnabled } from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/utils/tabsData';
import { openKYCFormUtil } from 'merchant/views/PartnerDashboard/SubMerchant/utils/navigation';
import { PRODUCT_ROUTE_PATH_PREFIX } from 'merchant/views/PartnerDashboard/constants';
import usePartnerDashboardExperiments from 'merchant/views/PartnerDashboard/hooks/usePartnerDashboardExperiments';
import { showNotification } from 'merchant_common/reducers/notifications';

import { trackAccountLevelAcceptedInvitesCta } from './utils/analytics';

type ActionButtonKYCProps = {
  submerchant: PGAcceptedInviteItem;
  user: User;
  productType: string;
  showNotification: ShowNotificationType;
};
const ActionButtonKYC = ({
  submerchant,
  user,
  productType,
  showNotification,
}: ActionButtonKYCProps): ReactNode => {
  const experiments = usePartnerDashboardExperiments();
  const [isActionLoading, setIsActionLoading] = useState(false);
  const navigate = useNavigate();

  const { isInviteFlowEnabled } = getIsInviteFlowEnabled(productType, experiments);

  const { details: { activation_status } = {}, kyc_access } = submerchant;
  const isSubMerchantKYCAccess = user?.isFeatureEnabled('partner_sub_kyc_access');
  const submerchantId = submerchant?.id;

  let state = kyc_access?.state;
  const token_expiry = moment.unix(kyc_access?.token_expiry as number);
  const rejectionCount = kyc_access?.rejection_count as number;

  let isDisabled = false;
  let isFullRejected = false;
  let btnText = 'Request for KYC';
  const openSidePannel = () => {
    if (isInviteFlowEnabled) {
      trackAccountLevelAcceptedInvitesCta(submerchant, { productType, action: btnText });
    }
    navigate(`/partners/submerchants${PRODUCT_ROUTE_PATH_PREFIX[productType]}/${submerchantId}`);
  };
  const openKYCForm = () => {
    const isMWeb = isMobileAndTablet();
    if (isInviteFlowEnabled) {
      trackAccountLevelAcceptedInvitesCta(submerchant, { productType, action: btnText });
    }
    if (!isActionLoading) {
      setIsActionLoading(true);
      openKYCFormUtil(isMWeb, navigate, submerchant, showNotification).then(() => {
        setIsActionLoading(false);
      });
    }
  };

  let action = openSidePannel;

  const currentTime = moment();
  const isApprovalRequestExpired = currentTime.isAfter(token_expiry);
  if (state === 'pending_approval' && isApprovalRequestExpired) {
    state = 'expired';
  }

  if (state === 'pending_approval') {
    isDisabled = true;
    btnText = 'Perform KYC';
  }
  if (state === 'expired') {
    action = openSidePannel;
    btnText = 'Resend KYC request';
  }
  if (state === 'approved') {
    btnText = 'Perform KYC';
    action = openKYCForm;
  }
  if (state === 'rejected') {
    btnText = 'Resend KYC request';
    action = openSidePannel;
    if (rejectionCount >= 3) {
      isFullRejected = true;
      btnText = 'Rejected Multiple times';
    }
  }
  if (isSubMerchantKYCAccess) {
    btnText = 'Perform KYC';
    action = openKYCForm;
    isFullRejected = false;
    isDisabled = false;
  }

  if (activation_status === 'needs_clarification') {
    btnText = 'Resubmit KYC details';
    action = openKYCForm;
  }

  if (
    [
      'activated',
      'activated_mcc_pending',
      'under_review',
      'kyc_qualified_unactivated',
      'rejected',
    ].includes(activation_status as string)
  ) {
    isDisabled = true;
  }
  const disabledClass = isDisabled ? 'action-kyc-request-disable' : '';
  const fullRejectClass = isFullRejected ? 'action-kyc-request-full-rejected' : '';

  return (
    <div className={`action-kyc-request ${disabledClass} ${fullRejectClass}`}>
      <Button
        variant="secondary"
        size="small"
        onClick={action}
        isLoading={isActionLoading}
        isDisabled={isDisabled}
      >
        {btnText}
      </Button>
    </div>
  );
};

export default compose(
  connect(
    (state) => ({ user: state.session.user }),
    (dispatch) => bindActionCreators({ showNotification }, dispatch),
  ),
)(ActionButtonKYC);
