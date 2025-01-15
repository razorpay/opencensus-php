import { useSplitzService } from 'common/splitz';
import { isExperimentEnabled } from 'common/splitz/utils';

export const useInsightXSplitzExperiments = () => {
  const {
    abExperiments: { insight_x_experiment },
  } = useSplitzService();

  return {
    isExperimentEnabled: insight_x_experiment?.variables?.result === 'on',
  };
};
