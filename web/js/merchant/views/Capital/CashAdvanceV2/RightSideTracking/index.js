import React from 'react';
import { APPLICATION_STATES, APPLICATION_STATE_SEQUENCE } from '../constants';
import {
  isCashAdvanceApplicationUnderReview,
  computeStatusData,
  getParentStepStatus,
} from './utils';
import ApplicationStatusTracker from './ApplicationStatusTracker';

const RightSideNavigation = ({ currentNavigationStatus, applicationStatus, handleCtaClick }) => {
  const getStatus = computeStatusData(
    applicationStatus || currentNavigationStatus,
    APPLICATION_STATES,
    APPLICATION_STATE_SEQUENCE,
  );

  // const creditRequiredStatus = getStatus(APPLICATION_STATES.CREDIT_REQUIRED);
  const businessDetailsStatus = getStatus(APPLICATION_STATES.BUSINESS_DETAILS_PENDING);
  const presonalDetailsStatus = getStatus(APPLICATION_STATES.PERSONAL_DETAILS_PENDING);
  const creditVerificationStatus =
    getStatus(APPLICATION_STATES.CREDIT_PULL_PENDING) ||
    getStatus(APPLICATION_STATES.CREDIT_PULL_FAILED);
  const bankStatementStatus = getStatus(APPLICATION_STATES.PREVERIFICATION_UPLOAD_PENDING);
  const isApplicationUnderReview = isCashAdvanceApplicationUnderReview(applicationStatus);
  const offerAcceptedStatus = getStatus(APPLICATION_STATES.CREDIT_OFFER_GENERATED);
  // const esignStatus =
  //   getStatus(APPLICATION_STATES.ESIGN_PENDING) || getStatus(APPLICATION_STATES.ESIGN_EXPIRED);
  const kycDocumentStatus = getStatus(APPLICATION_STATES.OFFLINE_DOCUMENT_COLLECTION_PENDING);
  const withdrawingStatus = getStatus(APPLICATION_STATES.STATE_COMPLETED);

  const getSubStepStatus = (state) => {
    switch (state) {
      // case APPLICATION_STATES.CREDIT_REQUIRED:
      //   return finalCreditRequiredStatus;
      case APPLICATION_STATES.BUSINESS_DETAILS_PENDING:
        return businessDetailsStatus;
      case APPLICATION_STATES.PERSONAL_DETAILS_PENDING:
        return presonalDetailsStatus;
      case APPLICATION_STATES.CREDIT_PULL_FAILED:
      case APPLICATION_STATES.CREDIT_PULL_PENDING:
        return creditVerificationStatus;
      case APPLICATION_STATES.PREVERIFICATION_UPLOAD_PENDING:
        return bankStatementStatus;
      case APPLICATION_STATES.CREDIT_OFFER_PENDING:
        return 'pending';
      case APPLICATION_STATES.CREDIT_OFFER_GENERATED:
        return offerAcceptedStatus;
      case APPLICATION_STATES.OFFLINE_DOCUMENT_COLLECTION_PENDING:
        return kycDocumentStatus;
      // case APPLICATION_STATES.ESIGN_PENDING:
      //   return finalEsignStatus;
      case APPLICATION_STATES.STATE_COMPLETED:
        return withdrawingStatus;
      default:
        return 'locked';
    }
  };

  return (
    <div>
      <ApplicationStatusTracker
        getSubStepStatus={getSubStepStatus}
        currentNavigationStatus={currentNavigationStatus}
        getParentStepStatus={getParentStepStatus}
        handleCtaClick={handleCtaClick}
        isApplicationUnderReview={isApplicationUnderReview}
      />
    </div>
  );
};

export default RightSideNavigation;
