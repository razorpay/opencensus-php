import {
  APPLICATION_STATE_SEQUENCE,
  APPLICATION_STATES,
  ERROR_STATES,
} from '../Loans/constants';

const getApplicationProgressPercentage = currentState => {
  if (ERROR_STATES.includes(currentState)) {
    currentState =
      APPLICATION_STATE_SEQUENCE[
        APPLICATION_STATE_SEQUENCE.indexOf(currentState) - 1
      ];
  }
  const nonFailedStates = APPLICATION_STATE_SEQUENCE.filter(
    state => !ERROR_STATES.includes(state)
  );

  const numerator = nonFailedStates.indexOf(currentState);
  const denominator = nonFailedStates.length;
  const percentage = (numerator / (denominator - 1)) * 100;
  return !percentage
    ? 0
    : percentage > 0
    ? percentage > 100
      ? 100
      : Math.ceil(percentage)
    : 0;
};

export default getApplicationProgressPercentage;
