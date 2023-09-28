import React from 'react';
import { connect } from 'react-redux';
import { compose } from 'redux';

import { withRouter } from 'common/deprecated/withRouter';
import {
  ICICIKYCStatus,
  PAN_VERIFICATION_STATUSES,
  panVerificationFailedStatuses,
} from 'common/ui/NotificationsDropdown/Neostone/Tracker/ICICITrackerStatus';
import {
  bankNamesMap,
  derivedCaApplicationStatus,
} from 'common/ui/NotificationsDropdown/Neostone/Tracker/TrackerConstant';
import { XCACTATypes } from 'common/ui/NotificationsDropdown/Neostone/TypeDeclare/XCATypeDeclare';
import { getXBaseURL } from 'common/ui/NotificationsDropdown/Neostone/common/utils';
import {
  setActivePageName as fnSetActivePageName,
  setBaseLocation as fnSetBaseLocation,
} from 'merchant/reducers/app';

const getCTADetails = (proceededBank, bankStatusForCTA, iciciPan) => {
  let label = '',
    url = '',
    type: string | null = null;
  if (proceededBank === bankNamesMap.RBL) {
    if (bankStatusForCTA === derivedCaApplicationStatus.PAN_FAILED) {
      label = 'Enter PAN';
      url = `${getXBaseURL()}/current-account-application/pre-allocated`;
      type = 'primary';
    } else if (bankStatusForCTA === derivedCaApplicationStatus.PENDING) {
      label = 'Continue Your Application';
      url = `${getXBaseURL()}/current-account-application/pre-allocated`;
      type = 'primary';
    } else if (
      bankStatusForCTA === derivedCaApplicationStatus.RECIEVED ||
      bankStatusForCTA === derivedCaApplicationStatus.INITIATED
    ) {
      label = 'View Docs';
      url = `https://razorpay.com/docs/razorpayx/current-account/company/#documents-required`;
      type = 'grayed';
    }
  } else if (proceededBank === bankNamesMap.ICICI) {
    if (
      bankStatusForCTA === ICICIKYCStatus.created ||
      bankStatusForCTA === ICICIKYCStatus.user_submitted
    ) {
      if (panVerificationFailedStatuses.includes(iciciPan)) {
        label = 'Enter PAN';
        url = `${getXBaseURL()}/current-account-application/pre-allocated`;
        type = 'primary';
      } else if (iciciPan === PAN_VERIFICATION_STATUSES.verified) {
        label = 'View Docs';
        url = `https://razorpay.com/docs/razorpayx/current-account/company/#documents-required`;
        type = 'grayed';
      } else if (!iciciPan) {
        label = 'Continue Your Application';
        url = `${getXBaseURL()}/current-account-application/pre-allocated`;
        type = 'primary';
      }
    } else if (bankStatusForCTA === ICICIKYCStatus.sent_to_bank) {
      label = 'View Docs';
      url = `https://razorpay.com/docs/razorpayx/current-account/company/#documents-required`;
      type = 'grayed';
    }
  }

  return { label, url, type };
};

const XcaCTA = ({
  bankStatusForCTA,
  proceededBank,
  iciciPan,
  currentStatusMsg,
  setActivePageName,
  setBaseLocation,
  history,
}: XCACTATypes): React.ReactElement | null => {
  const { label, type, url, component } =
    currentStatusMsg?.[0].cta || getCTADetails(proceededBank, bankStatusForCTA, iciciPan) || {};

  const className = type === 'primary' ? 'btn btn-primary submit-btn' : 'btn btn-grayed';

  const handleConnectedBankingFlow = () => {
    history.push({
      pathname: '/connected-banking/icici-linked-ca',
      state: { showState: 'iframe', url },
    });
    setBaseLocation?.('/connected-banking/icici-linked-ca');
    setActivePageName?.('Connected Banking');
  };

  const handleCTAClick = () => {
    if (component === 'iframe') handleConnectedBankingFlow();
  };

  if (type) {
    return (
      <a
        type="button"
        className={className}
        target="__blank"
        href={component ? undefined : url}
        onClick={component ? handleCTAClick : undefined}
      >
        {label}
      </a>
    );
  }

  return null;
};

export default compose(
  connect(null, {
    setActivePageName: fnSetActivePageName,
    setBaseLocation: fnSetBaseLocation,
  }),
)(withRouter<XCACTATypes>(XcaCTA));
