import experimentDataMap from './experimentDataMap';
import { init, getVariants } from './splitzSetup';
import { isProductionEnv } from '@libs/shared-utils';
import { getClientID } from '@libs/web-nexus/common/services/tracking/segment';

const isProdEnv = isProductionEnv();

export default async function Splitz() {
  const PROD_API_BASE_URL = 'api.razorpay.com';
  const STAGE_API_BASE_URL = 'beta-api.stage.razorpay.in';

  /** splitz initialization */
  init({
    id: getClientID(),
    base_url: isProdEnv ? PROD_API_BASE_URL : STAGE_API_BASE_URL,
  });

  /** Converting experimentDataMap Object to Array of Objects required for splitz getVariants function */
  const experimentData = Object.values(experimentDataMap).map((item) => {
    return {
      experiment: {
        experimentId: isProdEnv ? item?.prod_exp_id : item?.stage_exp_id,
        variables: item?.experiment_variable,
        trackImpression: item?.trackImpression ?? true,
      },
      defaultVariant: {
        default_variant: item?.default_variant,
      },
    };
  });

  const splitzResult = await getVariants(experimentData);

  /** Injecting Evaluated experiment list to global store */
  window.splitz_experiment = Object.assign({}, ...splitzResult);
}

const evaluateExperiment = (experimentData) => {
  const splitzExperiments = window.splitz_experiment;

  if (!splitzExperiments) return false;

  const splitzExperimentVariant =
    splitzExperiments[isProdEnv ? experimentData?.prod_exp_id : experimentData?.stage_exp_id];
  if (
    splitzExperimentVariant?.default_variant === experimentData.default_variant ||
    !splitzExperimentVariant?.variables
  ) {
    return false;
  }

  return splitzExperimentVariant?.name === experimentData?.experiment_variable;
};

/** Functions to check experiment state */

export const isOnboardAllAsResellers = () => {
  return evaluateExperiment(experimentDataMap.partner_onboard_all_as_resellers);
};
