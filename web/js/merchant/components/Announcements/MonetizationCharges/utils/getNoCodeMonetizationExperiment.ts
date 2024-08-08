import { useSplitzService } from 'common/splitz';

export const NOCODE_MONETIZATION_EXPERIMENT = 'nocode_monetization';

export const getNoCodeMonetizationExperiment = (): boolean => {
  const experimentName = NOCODE_MONETIZATION_EXPERIMENT;
  const { abExperiments } = useSplitzService();
  return abExperiments?.[experimentName]?.variables?.result === 'on';
};
