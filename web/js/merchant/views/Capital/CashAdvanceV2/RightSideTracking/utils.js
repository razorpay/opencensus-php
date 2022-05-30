import { APPLICATION_STATES } from '../constants';

export const isCashAdvanceApplicationUnderReview = (state) =>
  [
    APPLICATION_STATES.PREVERIFICATION_IN_PROGRESS,
    APPLICATION_STATES.CREDIT_OFFER_PENDING,
  ]?.includes(state);

export const computeStatusData = (status, applicationStates, applicationStateSequence) => {
  const currentStateIndex = applicationStateSequence.indexOf(status);
  const statuses = {
    completed: applicationStateSequence.slice(0, currentStateIndex),
    locked: applicationStateSequence.slice(currentStateIndex + 1),
    failed: [],
    active: [],
  };

  if (status === applicationStates.CREDIT_PULL_FAILED) {
    statuses.failed?.push(applicationStates.CREDIT_PULL_FAILED);
  } else if (status === applicationStates.CREDIT_PULL_PENDING) {
    statuses.active.push(status);
  } else if (status === applicationStates.ESIGN_EXPIRED) {
    statuses.failed?.push(applicationStates.ESIGN_EXPIRED, applicationStates.ESIGN_PENDING);
  } else {
    statuses.active.push(status);
  }

  return (current) => {
    if (statuses.failed?.includes(current)) return 'failed';
    else if (statuses.completed?.includes(current)) return 'success';
    else if (statuses.locked?.includes(current)) return 'locked';
    else return 'active';
  };
};

export const getParentStepStatus = (state) => {
  const STAGE_STATE = {
    STAGE_1: 'locked',
    STAGE_2: 'locked',
    STAGE_3: 'locked',
  };
  switch (state) {
    case APPLICATION_STATES.BUSINESS_DETAILS_PENDING:
    case APPLICATION_STATES.PERSONAL_DETAILS_PENDING:
    case APPLICATION_STATES.CREDIT_PULL_PENDING:
    case APPLICATION_STATES.CREDIT_PULL_FAILED:
    case APPLICATION_STATES.PREVERIFICATION_UPLOAD_PENDING:
    case APPLICATION_STATES.PREVERIFICATION_IN_PROGRESS:
    case APPLICATION_STATES.PREVERIFICATION_FAILED:
    case APPLICATION_STATES.SCORE_GENERATION_PENDING:
    case APPLICATION_STATES.CREDIT_OFFER_PENDING: {
      return { ...STAGE_STATE, STAGE_1: 'success' };
    }
    case APPLICATION_STATES.CREDIT_OFFER_GENERATED:
      return {
        ...STAGE_STATE,
        STAGE_1: 'success',
        STAGE_2: 'active',
      };
    case APPLICATION_STATES.OFFLINE_DOCUMENT_COLLECTION_PENDING:
      // case APPLICATION_STATES.ESIGN_PENDING:
      return {
        ...STAGE_STATE,
        STAGE_1: 'success',
        STAGE_2: 'success',
        STAGE_3: 'active',
      };
    case APPLICATION_STATES.STATE_COMPLETED:
      return { STAGE_4: 'success' };
    default:
      return false;
  }
};
