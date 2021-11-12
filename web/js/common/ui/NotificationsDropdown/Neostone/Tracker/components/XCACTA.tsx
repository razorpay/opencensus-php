import { bankNamesMap, derivedCaApplicationStatus } from '../TrackerConstant';
import {
  ICICIKYCStatus,
  PAN_VERIFICATION_STATUSES,
  panVerificationFailedStatuses,
} from '../ICICITrackerStatus';
import { getXBaseURL } from '../../common/utils';
import React from 'react';

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

const XcaCTA = ({ bankStatusForCTA, proceededBank, iciciPan }): React.ReactElement | null => {
  const { label, type, url } = getCTADetails(proceededBank, bankStatusForCTA, iciciPan);

  const className = type === 'primary' ? 'btn btn-primary submit-btn' : 'btn btn-grayed';

  if (type) {
    return (
      <a type="button" className={className} target="__blank" href={url}>
        {label}
      </a>
    );
  }

  return null;
};

export default XcaCTA;
