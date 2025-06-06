import React, { ReactNode, useState } from 'react';
import { Button } from '@razorpay/blade/components';
import moment from 'moment';
import { connect } from 'react-redux';
import { useNavigate } from 'react-router-dom';
import { bindActionCreators, compose } from 'redux';

import { ShowNotificationType, User } from 'common/typings';
import { isMobileAndTablet } from 'common/utils/rzp-utils';
import { POSAcceptedInviteItem } from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/ProductClientAccounts/common/api';
import { getIsInviteFlowEnabled } from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/utils/tabsData';
import { sendKYCRequest } from 'merchant/views/PartnerDashboard/SubMerchant/api';
import { openKYCFormUtil } from 'merchant/views/PartnerDashboard/SubMerchant/utils/navigation';
import {
  PARTNER_TYPE,
  PRODUCT_ROUTE_PATH_PREFIX,
  PRODUCT_TYPE,
} from 'merchant/views/PartnerDashboard/constants';
import usePartnerDashboardExperiments from 'merchant/views/PartnerDashboard/hooks/usePartnerDashboardExperiments';
import { showNotification } from 'merchant_common/reducers/notifications';

import { trackAccountLevelAcceptedInvitesCta } from './utils/analytics';

type ActionButtonKYCProps = {
  submerchant: POSAcceptedInviteItem;
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

  const isAggregator = user?.isPartner?.(PARTNER_TYPE.AGGREGATOR);

  const submerchantId = submerchant?.id;
  const MAX_REJECTION_COUNT = isAggregator ? 10 : 3;

  let state = kyc_access?.state;
  const token_expiry = moment.unix(kyc_access?.token_expiry as number);
  const rejectionCount = kyc_access?.rejection_count as number;

  let isDisabled = false;
  let isFullRejected = false;
  let btnText = isAggregator ? 'Request for account access' : 'Request for KYC';
  const openSidePanel = () => {
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

  const handleKYCRequest = () => {
    sendKYCRequest(submerchantId.replace('acc_', ''))
      .then(() => {
        showNotification({
          type: 'success',
          message:
            'We have sent a mail to the merchant to approve your request. You will receive an email once the request has been approved',
        });
      })
      .catch((err: { errors: Array<string> }) => {
        showNotification({
          type: 'error',
          message: err.errors,
        });
      });
  };

  let action = handleKYCRequest;

  const currentTime = moment();
  const isApprovalRequestExpired = currentTime.isAfter(token_expiry);

  /**
   * The following conditions update the KYC button configuration based on the current state and related conditions.
   *
   * adjustments for button text, action, disabled state, and rejection flag depending on
   * the state of the KYC request (e.g., pending approval, expired, approved, or rejected). It also
   * handles special cases for sub-merchant KYC access and activation statuses.
   */
  if (state === 'pending_approval' && isApprovalRequestExpired) {
    state = 'expired';
  }
  if (state === 'pending_approval') {
    isDisabled = true;
    btnText = 'Perform KYC';
  }
  if (state === 'expired') {
    action = openSidePanel;
    btnText = 'Resend KYC request';
  }
  if (state === 'approved') {
    btnText = 'Perform KYC';
    action = openKYCForm;
  }
  if (state === 'rejected') {
    btnText = 'Resend KYC request';
    action = openSidePanel;
    if (rejectionCount >= MAX_REJECTION_COUNT) {
      isFullRejected = true;
      isDisabled = true;
    }
  }
  if (isSubMerchantKYCAccess) {
    btnText = 'Perform KYC';
    action = openKYCForm;
    isFullRejected = false;
    isDisabled = false;
  }

  if (
    activation_status === 'needs_clarification' ||
    (productType === PRODUCT_TYPE.POS &&
      submerchant?.pos?.activation_status === 'needs_clarification')
  ) {
    btnText = 'Resubmit KYC details';
    action = openKYCForm;
    isDisabled = false;
  }

  if (
    [
      'activated',
      'activated_mcc_pending',
      'under_review',
      'kyc_qualified_unactivated',
      'rejected',
      'edd_pending',
    ].includes(activation_status as string) &&
    !(
      productType === PRODUCT_TYPE.POS &&
      submerchant?.pos?.activation_status === 'needs_clarification'
    )
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
