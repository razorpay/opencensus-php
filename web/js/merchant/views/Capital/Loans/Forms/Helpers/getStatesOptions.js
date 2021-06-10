import { states } from 'merchant/helpers/data';

export const statesOptions = Object.entries(states).map(([stateCode, label]) => ({
  name: stateCode,
  label,
}));
