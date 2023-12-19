import { server, waitFor } from 'test-utils';

import GrowthService from 'merchant/models/GrowthService/GrowthService';

import { fetchAssetData } from './mocks/handlers/GrowthService';

describe('Tests for `GrowthService` class', () => {
  beforeAll(() => {
    window.rzp_user = {
      current: 'xxxxxxxxxxxxxx',
    };
  });

  afterAll(() => {
    window.rzp_user = undefined;
  });

  describe('Test for `getAnnouncements` method', () => {
    test('Correct number of announcements should be returned with API call when channel matches', async () => {
      server.use(fetchAssetData());
      const growthService = new GrowthService();

      const announcements: Array<unknown> = await waitFor(() =>
        growthService.getAnnouncements('home'),
      );

      expect(announcements.length).toBe(2);
    });

    test('No announcements should be returned with API call when channel does not match', async () => {
      server.use(fetchAssetData({ doesChannelNotMatch: true }));
      const growthService = new GrowthService();

      const announcements: Array<unknown> = await waitFor(() =>
        growthService.getAnnouncements('home'),
      );

      expect(announcements.length).toBe(0);
    });

    test('No announcements should be added if there is an error in API call', async () => {
      server.use(fetchAssetData({ gsLevelStatus: 400 }));
      const growthService = new GrowthService();

      const announcements: Array<unknown> = await waitFor(() =>
        growthService.getAnnouncements('home'),
      );

      expect(announcements.length).toBe(0);
    });

    test('Announcements should be sorted', async () => {
      server.use(fetchAssetData());
      const growthService = new GrowthService();

      const announcements: Array<{ id: string }> = await waitFor(() =>
        growthService.getAnnouncements('home'),
      );

      expect(
        announcements.findIndex(
          ({ id }) => id === 'sub_campaign_debug_MAR1523_ANNOUNCEMENT_LRmF2MWxnhDt0w',
        ),
      ).toBe(0);
      expect(announcements.findIndex(({ id }) => id === 'May22_SSL_Razorpay_API_Update')).toBe(1);
    });

    test('Invalid announcements must be removed', async () => {
      server.use(fetchAssetData({ invalidateData: true }));
      const growthService = new GrowthService();

      const announcements: Array<unknown> = await waitFor(() =>
        growthService.getAnnouncements('home'),
      );

      expect(announcements.length).toBe(1);
    });
  });

  describe('Test for `getBanners` method', () => {
    test('Correct number of banners should be returned with API call when channel matches', async () => {
      server.use(fetchAssetData());
      const growthService = new GrowthService();

      const banners: Array<unknown> = await waitFor(() => growthService.getBanners('home'));

      expect(banners.length).toBe(1);
    });

    test('No banner should be returned with API call when channel does not match', async () => {
      server.use(fetchAssetData({ doesChannelNotMatch: true }));
      const growthService = new GrowthService();

      const banners: Array<unknown> = await waitFor(() => growthService.getBanners('home'));

      expect(banners.length).toBe(0);
    });

    test('No banner should be added if there is an error in API call', async () => {
      server.use(fetchAssetData({ gsLevelStatus: 400 }));
      const growthService = new GrowthService();

      const banners: Array<unknown> = await waitFor(() => growthService.getBanners('home'));

      expect(banners.length).toBe(0);
    });

    test('Banners should be sorted', async () => {
      server.use(fetchAssetData());
      const growthService = new GrowthService();

      const banners: Array<{ id: string }> = await waitFor(() => growthService.getBanners('home'));

      expect(
        banners.findIndex(
          ({ id }) => id === 'Reports_Schedules_Campaign_AUG0123_BANNER_MKhow7WHYsf3zw',
        ),
      ).toBe(0);
    });

    test('Invalid banners must be removed', async () => {
      server.use(fetchAssetData({ invalidateData: true }));
      const growthService = new GrowthService();

      const banners: Array<{ id: string }> = await waitFor(() => growthService.getBanners('home'));

      expect(
        banners.findIndex(({ id }) => id === 'Razorpay_POS_Campaign_JUL1823_BANNER_MFDLVodxcJdsZT'),
      ).toBe(0);
    });
  });
});
