import { useSplitzService } from 'common/splitz';
import { isLowCostExperimentEnabled } from 'merchant/views/Offers/New/Screens/NoCostEMI/helpers/helper';

export const useLowCostOfferExperiment = (): { isLowCostEnabled: boolean } => {
  const {
    abExperiments: { Low_cost_offer },
  } = useSplitzService();

  return {
    isLowCostEnabled: isLowCostExperimentEnabled(Low_cost_offer),
  };
};
