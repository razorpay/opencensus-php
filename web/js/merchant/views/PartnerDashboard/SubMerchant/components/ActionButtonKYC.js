import { useState } from 'react';
import { Button } from '@razorpay/blade/components';
import moment from 'moment';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import { bindActionCreators, compose } from 'redux';

import { withRouter } from 'common/deprecated/withRouter';
import { isMobileAndTablet } from 'common/utils/rzp-utils';
import { openKYCFormUtil } from 'merchant/views/PartnerDashboard/SubMerchant/utils/navigation';
import { showNotification } from 'merchant_common/reducers/notifications';

import { trackAccountLevelAcceptedInvitesCta } from './utils/analytics';

const ActionButtonKYC = ({
  activation_status = null,
  kyc_access = null,
  submerchant,
  history,
  user,
  isPGProductWithInviteFlow,
  showNotification,
}) => {
  const isSubMerchantKYCAccess = user?.isFeatureEnabled('partner_sub_kyc_access');
  const submerchantId = submerchant?.id;
  const [isActionLoading, setIsActionLoading] = useState(false);
  let state = kyc_access?.state;
  const token_expiry = moment.unix(kyc_access?.token_expiry);
  const rejection_count = kyc_access?.rejection_count;
  let isDisabled = false;
  let isFullRejected = false;
  let btnText = 'Request for KYC';

  const openSidePannel = () => {
    if (isPGProductWithInviteFlow) {
      trackAccountLevelAcceptedInvitesCta(submerchant, {
        properties: { action: btnText },
      });
    }
    history.push(`/partners/submerchants/${submerchantId}`);
  };
  const openKYCForm = () => {
    const is_mweb = isMobileAndTablet();
    if (isPGProductWithInviteFlow) {
      trackAccountLevelAcceptedInvitesCta(submerchant, {
        properties: { action: btnText },
      });
    }
    if (!isActionLoading) {
      setIsActionLoading(true);
      openKYCFormUtil(is_mweb, history, submerchant, showNotification).then(() => {
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
    if (rejection_count >= 3) {
      isFullRejected = true;
      btnText = 'Rejected Multiple times';
    }
  }
  if (activation_status === 'needs_clarification') {
    btnText = 'Resubmit KYC details';
    action = openKYCForm;
  }
  if (isSubMerchantKYCAccess) {
    btnText = 'Perform KYC';
    action = openKYCForm;
    isFullRejected = false;
    isDisabled = false;
  }

  if (
    [
      'activated',
      'activated_mcc_pending',
      'under_review',
      'kyc_qualified_unactivated',
      'rejected',
    ].includes(activation_status)
  ) {
    return null;
  }
  const disabledClass = isDisabled ? 'action-kyc-request-disable' : '';
  const fullRejectClass = isFullRejected ? 'action-kyc-request-full-rejected' : '';

  return (
    <div className={`action-kyc-request ${disabledClass} ${fullRejectClass}`}>
      <Button variant="secondary" size="small" onClick={action} isLoading={isActionLoading}>
        {btnText}
      </Button>
    </div>
  );
};

ActionButtonKYC.propTypes = {
  activation_status: PropTypes.string,
  kyc_access: PropTypes.shape({
    state: PropTypes.string.isRequired,
    rejection_count: PropTypes.number.isRequired,
    token_expiry: PropTypes.number.isRequired,
  }),
  submerchant: PropTypes.object,
  user: PropTypes.object,
  showNotification: PropTypes.func,
};

export default compose(
  connect(
    (state) => ({ user: state.session.user }),
    (dispatch) => bindActionCreators({ showNotification }, dispatch),
  ),
  withRouter,
)(ActionButtonKYC);
