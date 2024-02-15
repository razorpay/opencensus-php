import {
  AlertOctagonIcon,
  AlertTriangleIcon,
  CheckIcon,
  Badge,
  InfoIcon,
  ClockIcon,
  Box,
} from '@razorpay/blade/components';
import moment from 'moment';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';

import PopoverComponent, { PopoverBody } from 'common/ui/Popover';
import { titleCase } from 'common/utils/rzp-utils';
import ConditionalTooltip from 'merchant/containers/ConditionalTooltip';

const statusMap = {
  activated: 'positive',
  rejected: 'negative',
  needs_clarification: 'notice',
  under_review: 'information',
  kyc_qualified_unactivated: 'information',
  instantly_activated: 'information',
  activated_mcc_pending: 'positive',
};

const activationStatusToIcon = {
  activated: CheckIcon,
  rejected: AlertOctagonIcon,
  needs_clarification: AlertTriangleIcon,
  under_review: ClockIcon,
  kyc_qualified_unactivated: ClockIcon,
  instantly_activated: CheckIcon,
  activated_mcc_pending: CheckIcon,
};

// API Resp
// "kyc_access": {
//   "state": "pending_approval" || "rejected" || "approved",
//   "rejection_count": number,
//   "token_expiry"
// } || null

const SubMerchantKycStatusLabel = ({
  activation_status = null,
  kyc_access = null,
  user,
  showDescriptionAsTooltip = false,
}) => {
  const isSubMerchantKYCAccess = user?.isFeatureEnabled('partner_sub_kyc_access');

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
    [
      'activated',
      'activated_mcc_pending',
      'under_review',
      'kyc_qualified_unactivated',
      'rejected',
    ].includes(activation_status)
  ) {
    description = ``;
  }

  if (isSubMerchantKYCAccess) {
    description = ``;
  }

  const getLabelColor = () => {
    if (activation_status === null) {
      return 'notice';
    }
    return statusMap[activation_status.toLowerCase()] || statusMap.needs_clarification;
  };

  const getLabelIcon = () => {
    if (activation_status === null) {
      return InfoIcon;
    }
    return (
      activationStatusToIcon[activation_status.toLowerCase()] ||
      activationStatusToIcon.needs_clarification
    );
  };

  const getLabelText = () => {
    if (activation_status === 'activated_mcc_pending') {
      return 'Activated';
    }
    if (activation_status === null) {
      return 'Pending Completion';
    }
    return titleCase(activation_status);
  };
  return (
    <Box
      minWidth="150px"
      className={`submerchant-kyc-status ${description ? '' : 'no-description'}`}
    >
      <ConditionalTooltip
        showTooltip={showDescriptionAsTooltip && !!description}
        content={description}
        onOpenChange={function noRefCheck() {}}
        placement="bottom"
      >
        <Badge size="large" icon={getLabelIcon()} color={getLabelColor()}>
          {getLabelText()}
        </Badge>
      </ConditionalTooltip>
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
      {!showDescriptionAsTooltip && description ? (
        <div className="submerchant-kyc-description"> {description} </div>
      ) : null}
    </Box>
  );
};

SubMerchantKycStatusLabel.propTypes = {
  activation_status: PropTypes.string,
  user: PropTypes.object,
  kyc_access: PropTypes.shape({
    state: PropTypes.string.isRequired,
    rejection_count: PropTypes.number.isRequired,
    token_expiry: PropTypes.number.isRequired,
  }),
};

export default connect((state) => ({ user: state.session.user }), null)(SubMerchantKycStatusLabel);
