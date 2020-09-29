import { ERROR_STATES } from '../Loans/constants';

const getApplicationProgressPercentage = (currentState, applicationStateGroups) => {
  const APPLICATION_STATES = Object.entries(applicationStateGroups).reduce(
    (acc, [_, states]) => [...acc, ...states],
    [],
  );

  if (ERROR_STATES.includes(currentState)) {
    currentState = APPLICATION_STATES[APPLICATION_STATES.indexOf(currentState) - 1];
  }
  const nonFailedStates = APPLICATION_STATES.filter((state) => !ERROR_STATES.includes(state));

  const currentStateIndex = nonFailedStates.indexOf(currentState);
  const totalStates = nonFailedStates.length;
  const percentage = (currentStateIndex / (totalStates - 1)) * 100;
  return !percentage ? 0 : percentage > 0 ? (percentage > 100 ? 100 : Math.ceil(percentage)) : 0;
};

export default getApplicationProgressPercentage;
