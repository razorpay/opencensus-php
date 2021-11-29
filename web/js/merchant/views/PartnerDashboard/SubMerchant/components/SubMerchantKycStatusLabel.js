import PropTypes from 'prop-types';
import { titleCase } from 'common/utils/rzp-utils';
import PopoverComponent, { PopoverBody } from 'common/ui/Popover';
import moment from 'moment';

const statusMap = {
  activated: 'label-light-positive',
  rejected: 'label-light-negative',
  needs_clarification: 'label-light-warning',
  under_review: 'label-light-neutral',
  instantly_activated: 'label-light-information',
  activated_mcc_pending: 'label-light-positive',
};

// API Resp
// "kyc_access": {
//   "state": "pending_approval" || "rejected" || "approved",
//   "rejection_count": number,
//   "token_expiry"
// } || null

const SubMerchantKycStatusLabel = ({ activation_status = null, kyc_access = null }) => {
  // May need to re-assign in future
  // eslint-disable-next-line prefer-const
  let customLabelStyle = null;
  let state = kyc_access?.state;
  const token_expiry = moment.unix(kyc_access?.token_expiry);
  const rejection_count = kyc_access?.rejection_count;

  let description = 'Request Merchant for KYC access';

  const currentTime = moment();
  const isApprovalRequestExpired = currentTime.isAfter(token_expiry);
  if (state === 'pending_approval' && isApprovalRequestExpired) {
    state = 'expired';
  }

  if (state === 'pending_approval') {
    description = 'Waiting for Merchant approval';
  }
  if (state === 'expired') {
    description = 'Request expired';
  }
  if (state === 'approved') {
    description = 'Request approved by Merchant';
  }
  if (state === 'rejected') {
    const count = {
      1: 'once',
      2: 'twice',
      3: 'thrice',
    };
    description = `Rejected by merchant ${count[rejection_count] || ''}`;
  }

  if (
    ['activated', 'activated_mcc_pending', 'under_review', 'rejected'].includes(activation_status)
  ) {
    description = ``;
  }

  const getLabelStyle = () => {
    if (customLabelStyle) {
      return customLabelStyle;
    }
    if (activation_status === null) {
      return 'label-light-warning';
    }
    return statusMap[activation_status.toLowerCase()];
  };

  const getLabelText = () => {
    if (activation_status === 'activated_mcc_pending') {
      return 'Activated';
    }
    if (activation_status === null) {
      return 'Not Submitted';
    }
    return titleCase(activation_status);
  };
  return (
    <div className={`submerchant-kyc-status ${description ? '' : 'no-description'}`}>
      <span class={`status-label label ${getLabelStyle()}`}>{getLabelText()}</span>
      {activation_status === 'instantly_activated' && (
        <>
          &nbsp;
          <i class="i i-info-circle" />
          <PopoverComponent align="right" theme="dark">
            <PopoverBody>
              The merchant can accept live payments but settlements will be on hold until KYC
              completion.
            </PopoverBody>
          </PopoverComponent>
        </>
      )}
      {description && <div className="submerchant-kyc-description"> {description} </div>}
    </div>
  );
};

SubMerchantKycStatusLabel.propTypes = {
  activation_status: PropTypes.string,
  kyc_access: PropTypes.shape({
    state: PropTypes.string.isRequired,
    rejection_count: PropTypes.number.isRequired,
    token_expiry: PropTypes.number.isRequired,
  }),
};
export default SubMerchantKycStatusLabel;
