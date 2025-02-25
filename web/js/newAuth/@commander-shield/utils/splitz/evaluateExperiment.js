/** check splitz experiment is enable for given experiment ID */
export const evaluateExperiment = (experimentData) => {
  const splitzExperiments = window.splitz_experiment;

  if (!splitzExperiments) return false;

  const splitzExperimentVariant =
    splitzExperiments[
      SHIELD_STAGE === 'production' ? experimentData?.prod_exp_id : experimentData?.stage_exp_id
    ];
  if (
    splitzExperimentVariant?.default_variant === experimentData.default_variant ||
    !splitzExperimentVariant?.variables
  ) {
    return false;
  }

  return splitzExperimentVariant?.variables?.[0]?.value === (experimentData?.value || 'on');
};
