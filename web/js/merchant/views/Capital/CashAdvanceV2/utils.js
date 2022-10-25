import { CAPITAL_PRODUCT_CODES } from 'merchant/views/Capital/Loans/constants';
import { APPLICATION_STATES } from './constants';
import {
  checkBusinessDetailsStatus,
  checkPersonalDetailsStatus,
  checkCreditPullStatus,
  checkPreverificationStatus,
  checkApplicationReviewStatus,
  checkOfflineDocumentStatus,
  checkEsignStatus,
} from 'merchant/views/Capital/utils/common';
import cloneDeep from 'lodash/cloneDeep';

export const getProductId = (products = [], productCode) => {
  return products.find((d) => d.name === productCode)?.id;
};

export const computeCashAdvanceApplicationStatus = ({ application, applicant }) => {
  const hasApplicationData = !!(
    application &&
    typeof application === 'object' &&
    Object.keys(application).length
  );

  if (!hasApplicationData) return APPLICATION_STATES.BUSINESS_DETAILS_PENDING;

  if (
    [
      APPLICATION_STATES.STATE_REJECTED,
      APPLICATION_STATES.RZP_REJECTED,
      APPLICATION_STATES.STATE_CLOSED,
    ]?.includes(application.state)
  ) {
    return application.state;
  }

  const businessDetails = checkBusinessDetailsStatus(application, applicant);
  if (businessDetails.pending) return APPLICATION_STATES.BUSINESS_DETAILS_PENDING;

  const personalDetails = checkPersonalDetailsStatus(application, applicant);
  if (personalDetails.pending) return APPLICATION_STATES.PERSONAL_DETAILS_PENDING;

  const creditPull = checkCreditPullStatus(application);
  if (creditPull.pending) return APPLICATION_STATES.CREDIT_PULL_PENDING;

  const preverification = checkPreverificationStatus(application);
  if (preverification.pending) return APPLICATION_STATES.PREVERIFICATION_UPLOAD_PENDING;

  const applicationReview = checkApplicationReviewStatus(application);
  if (applicationReview.pending) return applicationReview.status;

  const offlineDocuments = checkOfflineDocumentStatus(application);
  if (offlineDocuments.pending) return offlineDocuments.status;

  // const esignStatus = checkEsignStatus(application);
  // if (esignStatus.pending) return esignStatus.status;

  return APPLICATION_STATES.STATE_COMPLETED;
};

export const computeCorporateCardApplicationStatus = ({
  application: originApplication,
  applicant,
}) => {
  const application = cloneDeep(originApplication);

  if (application?.id) {
    const esignDocIndex = application?.documents?.findIndex(
      (document) => document?.document_group?.name === 'esign_document',
    );

    if (esignDocIndex >= 0) {
      application.post_offer_documents = application?.post_offer_documents?.filter(
        (postDocId) => postDocId !== application?.documents?.[esignDocIndex]?.id,
      );
      application?.documents?.splice(esignDocIndex, 1);
    }
  }

  const hasApplicationData = !!(
    application &&
    typeof application === 'object' &&
    Object.keys(application).length
  );

  if (!hasApplicationData) return APPLICATION_STATES.BUSINESS_DETAILS_PENDING;

  if (
    [
      APPLICATION_STATES.STATE_REJECTED,
      APPLICATION_STATES.RZP_REJECTED,
      APPLICATION_STATES.STATE_CLOSED,
    ]?.includes(application?.state)
  ) {
    return application?.state;
  }

  const businessDetails = checkBusinessDetailsStatus(application, applicant);
  if (businessDetails?.pending) return APPLICATION_STATES.BUSINESS_DETAILS_PENDING;

  const personalDetails = checkPersonalDetailsStatus(application, applicant);
  if (personalDetails?.pending) return APPLICATION_STATES.PERSONAL_DETAILS_PENDING;

  const creditPull = checkCreditPullStatus(application);
  if (creditPull?.pending) return APPLICATION_STATES.CREDIT_PULL_PENDING;

  const preverification = checkPreverificationStatus(application);
  if (preverification?.pending) return APPLICATION_STATES.PREVERIFICATION_UPLOAD_PENDING;

  const applicationReview = checkApplicationReviewStatus(application);
  if (applicationReview?.pending) return applicationReview.status;

  const offlineDocuments = checkOfflineDocumentStatus(application);
  if (offlineDocuments?.pending) return offlineDocuments.status;

  const esignStatus = checkEsignStatus(application);
  if (esignStatus?.pending) return esignStatus.status;

  return APPLICATION_STATES.STATE_COMPLETED;
};

const statusFnMap = {
  LOC: computeCashAdvanceApplicationStatus,
  CARDS: computeCorporateCardApplicationStatus,
};

const updateNavigation = (previousNavigation, updateApplicationStatus) => {
  return {
    ...previousNavigation,
    current: updateApplicationStatus,
    applicationStatus: updateApplicationStatus,
  };
};

export const getApplicationStatus = ({ application, applicant, productCode }) => {
  const fnToComputeStatus = statusFnMap[productCode];
  const updatedApplicationStatus = fnToComputeStatus({
    application,
    applicant,
  });

  return updatedApplicationStatus;
};

export const getFormattedApplicationData = (
  application,
  productCode = CAPITAL_PRODUCT_CODES.CASH_ADVANCE,
) => {
  const { business = {}, credit_offers = [] } = application;
  const { id: businessId = null, applicants = [] } = business;
  let payload = {
    businessId,
    business,
    application: application || null,
    creditOffers: credit_offers,
    navigation: {
      current: null,
      applicationStatus: null,
    },
  };

  if (applicants && applicants.length) {
    payload = {
      ...payload,
      applicant: applicants[0],
      applicantId: applicants[0].id,
    };
  }

  const updatedApplicationStatus = getApplicationStatus({
    application,
    applicant: payload.applicant,
    productCode,
  });

  payload.navigation = updateNavigation(payload.navigation, updatedApplicationStatus);
  return payload;
};
