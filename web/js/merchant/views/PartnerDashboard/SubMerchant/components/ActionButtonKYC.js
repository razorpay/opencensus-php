import PropTypes from 'prop-types';
import Button from '@razorpay/blade-old/src/atoms/Button';
import { withRouter } from 'react-router-dom';
import { isMobileAndTablet } from 'common/utils/rzp-utils';
import moment from 'moment';

const ActionButtonKYC = ({
  activation_status = null,
  kyc_access = null,
  submerchant,
  history,
  trackUserEvent,
}) => {
  const submerchantId = submerchant?.id;

  let state = kyc_access?.state;
  const token_expiry = moment.unix(kyc_access?.token_expiry);
  const rejection_count = kyc_access?.rejection_count;
  let isDisabled = false;
  let btnText = 'Request for KYC';

  const openSidePannel = () => {
    const is_mweb = isMobileAndTablet();
    trackUserEvent('partnerships.dashboard.affiliate_account.kyc_request', {
      activation_status,
      kyc_access_state: state,
      rejection_count,
      is_mweb,
      action: 'open_pannel',
      submerchant_id: submerchantId,
    });
    history.push(`/partners/submerchants/${submerchantId}`);
  };
  const openKYCForm = () => {
    const is_mweb = isMobileAndTablet();
    trackUserEvent('partnerships.dashboard.affiliate_account.kyc_request', {
      activation_status,
      kyc_access_state: state,
      rejection_count,
      is_mweb,
      action: 'kyc_form',
    });
    if (is_mweb) {
      history.push(`/partners/submerchants/onboarding/${submerchantId}/steps`);
    } else {
      history.push(`/partners/submerchants/${submerchantId}/activation`);
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
  }

  if (
    ['activated', 'activated_mcc_pending', 'under_review', 'rejected'].includes(activation_status)
  ) {
    return null;
  }
  return (
    <div className={`action-kyc-request ${isDisabled ? 'action-kyc-request-disable' : ''}`}>
      <Button variant="secondary" size="small" onClick={action}>
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
};

export default withRouter(ActionButtonKYC);
