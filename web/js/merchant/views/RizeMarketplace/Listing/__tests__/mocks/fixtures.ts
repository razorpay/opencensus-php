import { MARKETPLACE_CATEGORIES } from 'merchant/views/RizeMarketplace/common/constants';
import { ProductResponse, TYPE } from 'merchant/views/RizeMarketplace/common/types';

export const mockData: ProductResponse[] = [
  {
    id: 'd0rGoQjA',
    hidden: false,
    slug: 'test-product',
    data: {
      name: 'Test Product',
      excerpt: 'This is a deal',
      logo_src: '',
      video_src: 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
      type: TYPE.rize,
      category: MARKETPLACE_CATEGORIES[0],
      company: {
        company_id: '1',
        name: 'ABC',
        website_url: 'https://example.com',
        industry: 'SaaS',
        co_founders: [
          {
            name: 'Founder 1',
            profile_picture:
              '/uploads/Mol9MZ2rKVyJLp/avatars/b2312c22-ffc5-413c-abf1-4768dadbb63b.jpg',
            user_id: '1',
            username: 'founder-1',
          },
        ],
      },
      deal: {
        offer: '50% off',
        avail_link: 'https://example.com',
        coupon_code: 'FREE50',
      },
      details: {
        eligibility: 'lorem ipsum dolor sit amet consectetur adipiscing elit sed do ',
        features: [
          'lorem ipsum dolor sit amet consectetur adipiscing elit sed do eiusmod tempor',
          'incididunt ut labore et dolore magna aliqua',
        ],
        about: 'lorem ipsum dolor sit amet consectetur adipiscing elit',
        how_to_avail: 'Go to https://example.com, and follow instructions there',
      },
    },
  },
  {
    id: 'cjOSDJwe',
    hidden: false,
    slug: 'test-product-2',
    data: {
      name: 'Test Product 2',
      excerpt: 'This is a deal',
      logo_src: '',
      video_src: 'https://youtu.be/dQw4w9WgXcQ',
      type: TYPE.rize,
      category: MARKETPLACE_CATEGORIES[3],
      company: {
        company_id: '2',
        name: 'Test company',
        website_url: 'https://example.com',
        industry: 'SaaS',
        co_founders: [
          {
            name: 'Founder',
            profile_picture: '',
            user_id: '2',
            username: 'founder-2',
          },
        ],
      },
      deal: {
        offer: '10% off',
        avail_link: 'https://example.com',
        coupon_code: '',
      },
      details: {
        eligibility: 'lorem ipsum dolor sit amet consectetur adipiscing elit',
        features: [
          'lorem ipsum dolor sit amet consectetur adipiscing elit sed do eiusmod tempor',
          'incididunt ut labore et dolore magna aliqua',
        ],
        about: 'lorem ipsum dolor sit amet consectetur adipiscing elit sed do eiusmod',
        how_to_avail: 'lorem ipsum dolor sit amet consectetur',
      },
    },
  },
];
