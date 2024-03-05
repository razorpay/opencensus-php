import { MARKETPLACE_CATEGORIES } from 'merchant/views/RizeMarketplace/common/constants';
import { TYPE } from 'merchant/views/RizeMarketplace/common/types';

export const mockData = [
  {
    id: '1',
    name: 'ABC Deal',
    slug: 'abc-deal',
    excerpt:
      'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.',
    logo_src: 'https://example.com/',
    type: TYPE.rize,
    category: MARKETPLACE_CATEGORIES[0],
    offer: '20% off',
  },
  {
    id: '2',
    name: 'XYZ Deal',
    slug: 'xyz-deal',
    excerpt:
      'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.',
    logo_src: 'https://example.com',
    type: TYPE.rize,
    category: MARKETPLACE_CATEGORIES[1],
    offer: '50% off',
  },
];
