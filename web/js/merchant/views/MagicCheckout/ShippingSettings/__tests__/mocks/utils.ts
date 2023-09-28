import cloneDeep from 'lodash/cloneDeep';
import { INITIAL_STATE } from './fixtures';

export const getStateWithSelectedProfile = (name) => {
  const newState = cloneDeep(INITIAL_STATE);
  newState.magicShippingEngine.selected_profile = name;
  return newState;
};
