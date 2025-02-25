/*
    This file contains a map of Splitz A/B service experiment data for all environments

  The structures is : {
    [module_feature_name]: {
      stage_exp_id: dev/stage experiment ID,
      prod_exp_id: prod experiment ID,
      default_variant: default name incase experiment name,
      experiment_variable: Variant name,
      value: experiment on or off,
      trackImpression : (optional) default true
    }
  }
  Feature name can be anything which you can use to get list of experiment IDs in the codebase.
*/

export const experimentDataMap = {
  oauth_easy_onboarding: {
    stage_exp_id: 'MrFtKAtY44SUfd',
    prod_exp_id: 'MwMgeQHDwoff3d',
    default_variant: 'variables',
    experiment_variable: 'variables',
    trackImpression: true,
    value: 'on',
  },
};
