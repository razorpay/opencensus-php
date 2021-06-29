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
    'HF0Ml2IU6gH9rt', // Gandhinagar Nitro
    'HF0NZThSDtgNGB', // Vadodara Nitro
    'HF0OIJAqllZPRu', // Ahmedabad Nitro
    'HF0Ox4LNEgYHbV', // Bangalore Nitro

    // prod experiment ids
    'GxtSf8y77iWw9e',
    'H6qGPCBPduY7Gl', // Kolkata Nitro
    'H6qJ2X77dqHG9I', // Chennai Nitro
    'H6qIJWTzrqt54X', // Jaipur Nitro
    'H6qHJJnYOtwfoc', // Surat Nitro
    'HExafLb492K7LU', // Gandhinagar Nitro
    'HExehMbAqYqlWF', // Vadodara Nitro
    'HExiHP6GBUEVcu', // Ahmedabad Nitro
    'HExnzHcFfimA6u', // Bangalore Nitro
    'HPc6GXsuboNXiS', // Delhi Nitro
    'HPc7OB0N3kh5BR', // Mumbai Nitro
    'HPc8DrLeWZc76W', // Pune Nitro
    'HPc9cMyPKKeAAX', // Gurgaon Nitro
    'HPcAKrn53GP41d', // Nagpur Nitro
    'HPcBJQw2E0BzpZ', // Kolhapur Nitro
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
  first_usage_merchants_experiment: [
    // beta experiment ids
    'HPfpmgQiXlqfr8',
    // prod experiment ids
    'HPcWQJN0hMXgzK',
  ],
  whats_new_lazy_experiment: [
    // beta experiment ids
    'HPhSWihQaCQ2wJ',
    // prod experiment ids
    'HQqeKAnGRYPO4S',
  ],
};
