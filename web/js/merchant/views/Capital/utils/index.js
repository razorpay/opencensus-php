import { APPLICATION_STATE_SEQUENCE } from '../constants';

export const getDisbursalAmount = (
  creditOffered,
  processingFeePercentage,
  taxPercentage
) => {
  return (
    (parseInt(creditOffered) / 100) *
    (1 - (processingFeePercentage / 100) * (taxPercentage / 100)) *
    100
  );
};

export const isPreceedingState = (state1, state2) => {
  const state1Index = APPLICATION_STATE_SEQUENCE.indexOf(state1);
  const state2Index = APPLICATION_STATE_SEQUENCE.indexOf(state2);
  return state1Index < state2Index;
};

export const calculatePercentageAmount = (rate, credit_amount) => {
  return (rate * credit_amount) / 100;
};
