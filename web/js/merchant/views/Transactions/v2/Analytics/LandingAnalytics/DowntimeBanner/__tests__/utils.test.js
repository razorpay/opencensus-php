import { getDowntimeBannerConfig } from 'merchant/views/Transactions/v2/Analytics/LandingAnalytics/DowntimeBanner/utils/downtime';

import { ONGOING_DOWNTIMES } from './mocks/fixtures';

describe('DowntimeBanner config', () => {
  describe('Cards', () => {
    test('should show downtime msg - One issuer down', () => {
      const { bannerMessage, shouldShowBanner } = getDowntimeBannerConfig({
        activeDowntimes: ONGOING_DOWNTIMES.CARD_ISSUER,
      });
      expect(bannerMessage).toEqual(
        'We are currently experiencing downtime on Bank Of India that may impact your card payments. We are working with the bank(s) to resolve this at the earliest',
      );
      expect(shouldShowBanner).toBeTruthy();
    });
    test('should show downtime msg - One network down', () => {
      const { bannerMessage, shouldShowBanner } = getDowntimeBannerConfig({
        activeDowntimes: ONGOING_DOWNTIMES.CARD_NETWORK,
      });
      expect(bannerMessage).toEqual(
        'We are currently experiencing downtime on RUPAY. We are working with our partners to resolve this at the earliest.',
      );
      expect(shouldShowBanner).toBeTruthy();
    });
    test('should show downtime msg - Multiple methods down', () => {
      const { bannerMessage, shouldShowBanner } = getDowntimeBannerConfig({
        activeDowntimes: ONGOING_DOWNTIMES.CARD_MULTIPLE_METHODS,
      });
      expect(bannerMessage).toEqual(
        'We are currently experiencing downtime on some card payments. We are working with the banks to resolve this at the earliest',
      );
      expect(shouldShowBanner).toBeTruthy();
    });
    test('should show downtime msg - Multiple issuers down', () => {
      const { bannerMessage, shouldShowBanner } = getDowntimeBannerConfig({
        activeDowntimes: ONGOING_DOWNTIMES.CARD_ISSUER_MULTIPLE,
      });
      expect(bannerMessage).toEqual(
        'We are currently experiencing downtime on some card issuer banks. We are working with the banks to resolve this at the earliest',
      );
      expect(shouldShowBanner).toBeTruthy();
    });
    test('should show downtime msg - Multiple networks down', () => {
      const { bannerMessage, shouldShowBanner } = getDowntimeBannerConfig({
        activeDowntimes: ONGOING_DOWNTIMES.CARD_NETWORK_MULTIPLE,
      });
      expect(bannerMessage).toEqual(
        'We are currently experiencing downtime on some card networks. We are working with our partners to resolve this at the earliest',
      );
      expect(shouldShowBanner).toBeTruthy();
    });
  });

  describe('UPI', () => {
    test('should show downtime msg - Multiple methods down', () => {
      const { bannerMessage, shouldShowBanner } = getDowntimeBannerConfig({
        activeDowntimes: ONGOING_DOWNTIMES.UPI_MULTIPLE,
      });
      expect(bannerMessage).toEqual(
        'We are currently experiencing downtime on UPI payments. We are working with our partners to resolve this at the earliest',
      );
      expect(shouldShowBanner).toBeTruthy();
    });
    test('should show downtime msg - PSP down', () => {
      const { bannerMessage, shouldShowBanner } = getDowntimeBannerConfig({
        activeDowntimes: ONGOING_DOWNTIMES.UPI_PSP,
      });
      expect(bannerMessage).toEqual(
        'We are currently experiencing downtime on Google Pay. We are working with our partners to resolve this at the earliest',
      );
      expect(shouldShowBanner).toBeTruthy();
    });
    test('should show downtime msg - VPA handle down', () => {
      const { bannerMessage, shouldShowBanner } = getDowntimeBannerConfig({
        activeDowntimes: ONGOING_DOWNTIMES.UPI_VPA_HANDLE,
      });
      expect(bannerMessage).toEqual(
        'We are currently experiencing downtime on UPI VPA oksbi. We are working with our partners to resolve this at the earliest',
      );
      expect(shouldShowBanner).toBeTruthy();
    });
  });

  describe('Netbanking', () => {
    test('should show downtime msg - One bank downtime', () => {
      const { bannerMessage, shouldShowBanner } = getDowntimeBannerConfig({
        activeDowntimes: ONGOING_DOWNTIMES.NETBANKING,
      });
      expect(bannerMessage).toEqual(
        'We are currently experiencing downtime on Axis Bank. We are working with the bank(s) to resolve this at the earliest',
      );
      expect(shouldShowBanner).toBeTruthy();
    });

    test('should show downtime msg - Two banks downtime', () => {
      const { bannerMessage, shouldShowBanner } = getDowntimeBannerConfig({
        activeDowntimes: ONGOING_DOWNTIMES.NETBANKING_TWO_BANKS,
      });
      expect(bannerMessage).toEqual(
        'We are currently experiencing downtime on Axis Bank and CITI Bank. We are working with the bank(s) to resolve this at the earliest',
      );
      expect(shouldShowBanner).toBeTruthy();
    });

    test('should show downtime msg - Multiple banks downtime', () => {
      const { bannerMessage, shouldShowBanner } = getDowntimeBannerConfig({
        activeDowntimes: ONGOING_DOWNTIMES.NETBANKING_MULTIPLE_BANKS,
      });
      expect(bannerMessage).toEqual(
        'We are currently experiencing downtime on some netbanking methods. We are working with the banks to resolve this at the earliest',
      );
      expect(shouldShowBanner).toBeTruthy();
    });
  });

  describe('Emandate', () => {
    test('should show downtime msg - One bank downtime', () => {
      const { bannerMessage, shouldShowBanner } = getDowntimeBannerConfig({
        activeDowntimes: ONGOING_DOWNTIMES.EMANDATE,
      });
      expect(bannerMessage).toEqual(
        'We are currently experiencing downtime on Axis Bank emandate banks. We are working with the bank(s) to resolve this at the earliest',
      );
      expect(shouldShowBanner).toBeTruthy();
    });

    test('should show downtime msg - Two banks downtime', () => {
      const { bannerMessage, shouldShowBanner } = getDowntimeBannerConfig({
        activeDowntimes: ONGOING_DOWNTIMES.EMANDATE_TWO_BANKS,
      });
      expect(bannerMessage).toEqual(
        'We are currently experiencing downtime on Axis Bank and CITI Bank emandate banks. We are working with the bank(s) to resolve this at the earliest',
      );
      expect(shouldShowBanner).toBeTruthy();
    });

    test('should show downtime msg - Multiple banks downtime', () => {
      const { bannerMessage, shouldShowBanner } = getDowntimeBannerConfig({
        activeDowntimes: ONGOING_DOWNTIMES.EMANDATE_MULTIPLE_BANKS,
      });
      expect(bannerMessage).toEqual(
        'We are currently experiencing downtime on some emandate banks. We are working with the banks to resolve this at the earliest',
      );
      expect(shouldShowBanner).toBeTruthy();
    });
  });
});
