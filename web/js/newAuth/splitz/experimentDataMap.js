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
    prod_exp_id: 'LUAS1Of9UtpYoU',
    default_variant: 'not_exposed',
    experiment_variable: 'exposed',
    trackImpression: true,
  },
  partner_onboard_all_as_resellers: {
    stage_exp_id: 'JpWqxoqQa6aMOZ',
    prod_exp_id: 'JpbtDw1u1tmAH2',
    default_variant: 'not_exposed',
    experiment_variable: 'exposed',
    trackImpression: true,
  },
  signup_enabled: {
    stage_exp_id: 'LMba3ww0kU1boF',
    prod_exp_id: 'LN1A7n6cTobraV',
    default_variant: 'variables',
    experiment_variable: 'variables',
    trackImpression: true,
  },
};
