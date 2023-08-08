import { SplitzInitConfig } from 'common/splitz/types';

// default = merchant(i.e product and partner) and linkedAccount dashboard.

export const splitzConfig: SplitzInitConfig = {
  onInit: {
    default: [],
    merchant: [],
    linkedAccount: [],
    pokedex: [],
  },
  routeBased: [],
};
