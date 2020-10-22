import { APPLICATION_STATE_SEQUENCE } from '../Loans/constants';

export const getDisbursalAmount = (creditOffered, processingFeePercentage, taxPercentage) => {
  const processingFee = calculatePercentageAmount(processingFeePercentage, creditOffered);
  const taxAmount = calculatePercentageAmount(taxPercentage, processingFee);
  return creditOffered - processingFee - taxAmount;
};

export const isPreceedingState = (currentState, activeState) => {
  const currentStateIndex = APPLICATION_STATE_SEQUENCE.indexOf(currentState);
  const activeStateIndex = APPLICATION_STATE_SEQUENCE.indexOf(activeState);
  return currentStateIndex <= activeStateIndex;
};

export const calculatePercentageAmount = (rate, credit_amount) => {
  return (rate * credit_amount) / 100;
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
