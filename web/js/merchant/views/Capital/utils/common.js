import {
  APPLICATION_STATES,
  DOCUMENT_STATUSES,
} from 'merchant/views/Capital/CashAdvanceV2/constants';

// import { APPLICATION_NOT_SUBMITTED } from 'merchant/views/Capital/Loans/constants';

export const isObjectValid = (object) =>
  !!(object && typeof object === 'object' && Object.keys(object).length);

export const checkBusinessDetailsStatus = (application, applicant = {}) => {
  const { addresses, emails, kyc, phones } = applicant || {};
  const applicantData = { addresses, emails, kyc, phones };
  const hasApplicationRelavantData = Object.keys(applicantData).every((key) =>
    isObjectValid(applicantData[key]),
  );
  const completed = !!(application && hasApplicationRelavantData);

  return {
    pending: !completed,
    completed,
  };
};

export const checkPersonalDetailsStatus = (application, applicant = {}) => {
  const { addresses, emails, kyc, phones } = applicant || {};
  const applicantData = { addresses, emails, kyc, phones };
  const hasApplicationRelavantData = Object.keys(applicantData).every((key) =>
    isObjectValid(applicantData[key]),
  );
  const completed = !!(application && hasApplicationRelavantData);

  return {
    pending: !completed,
    completed,
  };
};

export const checkDocumentStatus = (data, documentType) => {
  const { documents = [] } = data;
  const document = documents.find((documentData) => {
    const {
      document_group: { name = '' },
    } = documentData;

    return name === documentType;
  });

  if (!document) throw new Error(`Invalid application data. ${documentType} not found`);

  const { status = '' } = document;
  const response = {
    pending: false,
    completed: true,
    status,
  };

  // no special handling of failed status as of now
  // failed will be considered pending only
  if (status === DOCUMENT_STATUSES.UPLOAD_PENDING || status === DOCUMENT_STATUSES.FAILED) {
    response.pending = true;
    response.completed = false;
    response.status = status;
  }

  return response;
};

export const checkCreditPullStatus = (application = {}) => {
  return checkDocumentStatus(application, 'credit_bureau_report');
};

export const checkApplicationReviewStatus = (application = {}) => {
  const { loc_offers = [], contract } = application;
  const offerGenerated = !!loc_offers?.length;
  const isContractGenerated = !!contract;

  let status = APPLICATION_STATES.CREDIT_OFFER_PENDING;

  if (offerGenerated) {
    status = APPLICATION_STATES.CREDIT_OFFER_GENERATED;
  } else {
    status = APPLICATION_STATES.CREDIT_OFFER_PENDING;
  }

  return {
    pending: !isContractGenerated,
    completed: isContractGenerated,
    status,
  };
};

export const checkOfflineDocumentStatus = (application = {}) => {
  const checkIfDocsUnApproved = application.documents.some(
    (item) =>
      item.status === DOCUMENT_STATUSES.UPLOAD_PENDING || item.status === DOCUMENT_STATUSES.FAILED,
  );

  const response = {
    pending: checkIfDocsUnApproved,
    completed: !checkIfDocsUnApproved,
    status: APPLICATION_STATES.OFFLINE_DOCUMENT_COLLECTION_PENDING,
  };

  return response;
};

export const checkPreverificationStatus = (application = {}) => {
  return checkDocumentStatus(application, 'income_proof');
};

function findDoc(name) {
  return (application) => application?.documents.find((d) => d.document_group.name === name);
}

export const getCreditDoc = findDoc('credit_bureau_report');
export const getIncomeDoc = findDoc('income_proof');

export const checkApplicationActiveState = (status) => {
  return status !== APPLICATION_STATES.CLOSED && status !== APPLICATION_STATES.STATE_REJECTED;
};

//Note: To be used in the flow when esign is introduced
// export const checkEsignStatus = (application = {}) => {
//   const singnatories = application.esign_invitees || [];

//   const response = {
//     pending: true,
//     completed: false,
//     status: CARDS_APPLICATION_STATES.ESIGN_PENDING,
//   };

//   if (!singnatories.length || singnatories === APPLICATION_NOT_SUBMITTED) return response;

//   if (isEsignExpired(singnatories)) {
//     response.status = CARDS_APPLICATION_STATES.ESIGN_EXPIRED;
//   } else if (isEsignCompleted(singnatories)) {
//     response.pending = false;
//     response.completed = true;
//   }

//   return response;
// };

// export const isEsignCompleted = (signatories) =>
//   signatories.every((signatory) => signatory.status === SIGNATORY_STATUS.completed);

// export const isEsignExpired = (signatories) =>
//   signatories.some((signatory) => signatory.status === SIGNATORY_STATUS.failed);
