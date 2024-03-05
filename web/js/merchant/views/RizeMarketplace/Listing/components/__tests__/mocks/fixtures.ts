import { mockData } from 'merchant/views/RizeMarketplace/Listing/__tests__/mocks/fixtures';

export const mockCouponDealData = {
  ...mockData[0].data?.deal,
  slug: mockData[0].slug,
};

export const mockLinkDealData = {
  ...mockData[1].data?.deal,
  slug: mockData[1].slug,
};
