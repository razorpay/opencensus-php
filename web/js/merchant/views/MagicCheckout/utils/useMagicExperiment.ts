import { useSplitzService } from 'common/splitz';

/**
 * Custom hook to return the value of result variable of an experiment
 */
export const useMagicExperiment = (experimentName: string): boolean => {
  const { abExperiments } = useSplitzService();
  return abExperiments?.[experimentName]?.variables?.result === 'on';
};
