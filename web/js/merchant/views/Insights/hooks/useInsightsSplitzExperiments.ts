import { useSplitzService } from 'common/splitz';

export const useInsightsSplitzExperiments = () => {
  const {
    abExperiments: {
      insights_experiment,
      insights_success_rate_experiment,
      insights_checkout_experiment,
      insights_checkout_magicx_experiment,
    },
  } = useSplitzService();

  return {
    isExperimentEnabled: insights_experiment?.variables?.result === 'on',
    isInsightsSuccessRateEnabled: insights_success_rate_experiment?.variables.result === 'on',
    isInsightsCheckoutEnabled: insights_checkout_experiment?.variables?.result === 'on',
    isInsightsCheckoutMagicXEnabled: insights_checkout_magicx_experiment?.variables.result === 'on',
  };
};
