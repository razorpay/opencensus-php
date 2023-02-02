/*
    This file contains a map of Splitz A/B service experiment data for all environments

  The structures is : {
    [module_feature_name]: {
      stage_exp_id: dev/stage experiment ID,
      prod_exp_id: prod experiment ID,
      default_variant: default name incase experiment name,
      experiment_variable: Variant name,
      trackImpression : (optional) default true
    }
  }
  Feature name can be anything which you can use to get list of experiment IDs in the codebase.
*/

export default {
  new_partner_signup: {
    stage_exp_id: 'KZ3T8q3CYvdx4z',
    prod_exp_id: 'KZ3PzinESUIs5D',
    default_variant: 'not_exposed',
    experiment_variable: 'exposed',
    trackImpression: true,
  },
};
