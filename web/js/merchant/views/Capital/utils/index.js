import { APPLICATION_STATE_SEQUENCE, CAPITAL_PRODUCT_CODES, ERROR_STATES } from '../Loans/constants';

export const getDisbursalAmount = (creditOffered, processingFeePercentage, taxPercentage) => {
  const processingFee = calculatePercentageAmount(processingFeePercentage, creditOffered);
  const taxAmount = calculatePercentageAmount(taxPercentage, processingFee);
  return creditOffered - processingFee - taxAmount;
};

export const isPreceedingState = (currentState, activeState, strict = false) => {
  const currentStateIndex = APPLICATION_STATE_SEQUENCE.indexOf(currentState);
  const activeStateIndex = APPLICATION_STATE_SEQUENCE.indexOf(activeState);
  return strict ? currentStateIndex < activeStateIndex : currentStateIndex <= activeStateIndex;
};

export const calculatePercentageAmount = (rateInBPS, credit_amount) => {
  return ((parseInt(rateInBPS) / 100) * parseInt(credit_amount)) / 100;
};

export function postToUrl(path, params, method = 'post') {
  const form = document.createElement('form');
  form.method = method;
  form.action = path;

  for (const key in params) {
    if (params.hasOwnProperty(key)) {
      const hiddenField = document.createElement('input');
      hiddenField.type = 'hidden';
      hiddenField.name = key;
      hiddenField.value = params[key];

      form.appendChild(hiddenField);
    }
  }

  document.body.appendChild(form);
  form.submit();
}

export const isLoanProduct = (productName) => productName === CAPITAL_PRODUCT_CODES.LOAN;

export const isCashAdvanceProduct = (productName) =>
  productName === CAPITAL_PRODUCT_CODES.CASH_ADVANCE;


export const getApplicationSteps = (applicationStateGroups) => {
  return Object.entries(applicationStateGroups).reduce(
    (acc, [_, states]) => [...acc, ...states],
    [],
  );
};

const getNonFailedSteps = (applicationSteps) => {
  return applicationSteps.filter((state) => !ERROR_STATES.includes(state));
};

export const getStepIndex = (step, applicationStateGroups) => {
  const APPLICATION_STATES = getApplicationSteps(applicationStateGroups);

  if (ERROR_STATES.includes(step)) {
    step = APPLICATION_STATES[APPLICATION_STATES.indexOf(step) - 1];
  }
  const nonFailedStates = getNonFailedSteps(APPLICATION_STATES);

  const stepIndex = nonFailedStates.indexOf(step);
  return stepIndex;
};

export const getApplicationProgressPercentage = (currentState, applicationStateGroups) => {
  const currentStateIndex = getStepIndex(currentState, applicationStateGroups);

  const nonFailedStates = getNonFailedSteps(getApplicationSteps(applicationStateGroups));
  const totalStates = nonFailedStates.length;
  const percentage = (currentStateIndex / (totalStates - 1)) * 100;
  return !percentage ? 0 : percentage > 0 ? (percentage > 100 ? 100 : Math.ceil(percentage)) : 0;
};
