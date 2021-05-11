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
    'H7361l13HhrBgO', // Kolkata Nitro
    'H75RfvQFecKHsT', // Chennai Nitro
    'H75Qu5SInWp3SQ', // Jaipur Nitro
    'H75Q0JHjnUd5xs', // Surat Nitro

    // prod experiment ids
    'GxtSf8y77iWw9e',
    'H6qGPCBPduY7Gl', // Kolkata Nitro
    'H6qJ2X77dqHG9I', // Chennai Nitro
    'H6qIJWTzrqt54X', // Jaipur Nitro
    'H6qHJJnYOtwfoc', // Surat Nitro
  ],
  pure_platform_signup: [
    // beta experiment ids
    'H70qyLStkzoFeS',

    // prod experiment ids
    'H725exSfQ8ZAPz',
  ],
  announcement_text_experiment: [
    // beta experiment ids
    'H7S9OMKVGV53ZZ',

    // prod experiment ids
    'H7UUNpcljDyGAn',
  ],
  whats_new_text_experiment: [
    // beta experiment ids
    'H7S8wOn3THVwUa',
    // prod experiment ids
    'H7UYFAJIqhQB0X',
  ],
};
