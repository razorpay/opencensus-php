import { ALL_OPTION } from '@apps/digital-bills/src/common/components/StoreFilterModal/constants';

import type { SlotOptionType } from '@apps/digital-bills/src/common/components/StoreFilterModal/types';

export const getLeftSlots = (options: {
  state: SlotOptionType[];
  city: SlotOptionType[];
  brand: SlotOptionType[];
}) => ({
  slot: [
    {
      label: 'Brands',
      value: 'brands',
      options: [ALL_OPTION].concat(options.brand).filter(Boolean),
    },
    {
      label: 'State',
      value: 'states',
      options: [ALL_OPTION].concat(options.state).filter(Boolean),
    },
    { label: 'City', value: 'cities', options: [ALL_OPTION].concat(options.city).filter(Boolean) },
  ],
});
