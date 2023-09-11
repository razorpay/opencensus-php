import { SpiltzContextState } from 'common/splitz/types';
import { isExperimentEnabled } from 'common/splitz/utils';

export const isPosExperimentEnabled = (splitz: SpiltzContextState): boolean => {
  const { abExperiments } = splitz || { abExperiments: { pos_onboarding: undefined } };
  return isExperimentEnabled(abExperiments.pos_onboarding);
};
