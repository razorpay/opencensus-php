/*
    This file contains a map of Splitz A/B service experiment IDs for all environments

  The structures is : {
    [module_feature_name]: [LIST OF SPLITZ IDs]
  }

  Feature name can be anything which you can use to get list of experiment IDs in the codebase.
*/

export default {
  project_nitro: [
    // beta experiment ids
    'GwPth7nhHNdMND',

    // prod experiment ids
    'GxtSf8y77iWw9e',
  ],
  pure_platform_signup: [
    // beta experiment ids
    'H70qyLStkzoFeS',

    // prod experiment ids
    'H725exSfQ8ZAPz',
  ],
};
