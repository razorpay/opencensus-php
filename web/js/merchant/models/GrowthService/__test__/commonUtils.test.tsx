import {
  getChannelID,
  getAssetTrackingProperties,
  sortAssetData,
  stringToLiteral,
} from 'merchant/models/GrowthService/commonUtils';
import { assetNames } from 'merchant/models/GrowthService/data';
import {
  empty_tracking_obj,
  card_id,
  eventName,
  trackingData,
  expectedTrackingObj,
  banners,
  sorted_banners,
  announcement,
  sorted_announcement,
} from './fixtures';

describe('getChannelId', () => {
  it('getChannelId function call with no params', () => {
    window.APP_ENV = 'production';
    expect(getChannelID()).toBe('HpP3cspZ3AcuV2');
  });

  it('getChannelId function call with valid path', () => {
    window.APP_ENV = 'production';
    expect(getChannelID('/partners')).toBe('KYvhzKIY0r6zhJ');
  });

  it('getChannelId function call with invalid path', () => {
    window.APP_ENV = 'production';
    expect(getChannelID('/partnersing')).toBe('xxxxxxxxxxxxxx');
  });

  it('getChannelId function call with no environement', () => {
    window.APP_ENV = '';
    expect(getChannelID()).toBe('xxxxxxxxxxxxxx');
  });
});

describe('getAssetTrackingProperties', () => {
  it('getAssetTrackingProperties function call without params', () => {
    expect(getAssetTrackingProperties()).toStrictEqual(empty_tracking_obj);
  });

  it('getAssetTrackingProperties function with params', () => {
    expect(getAssetTrackingProperties(card_id, trackingData, {}, eventName)).toStrictEqual(
      expectedTrackingObj,
    );
  });
});

describe('sortAssetData', () => {
  it('sortAssetData function call with no data to sort', () => {
    expect(sortAssetData(undefined, assetNames.ANNOUNCEMENT)).toBe(undefined);
  });

  it('sortAssetData function call with empty data to sort', () => {
    const empty_banners = [];
    sortAssetData(empty_banners, assetNames.ANNOUNCEMENT);
    expect(empty_banners).toBe(empty_banners);
  });

  it('sortAssetData function call with no asset name to sort', () => {
    const empty_banners = [];
    sortAssetData(empty_banners, assetNames.MODAL);
    expect(empty_banners).toBe(empty_banners);
  });

  it('sortAssetData function call with sort banners by priority', () => {
    sortAssetData(banners, assetNames.BANNER);
    expect(banners).toStrictEqual(sorted_banners);
  });

  it('sortAssetData function call with sort banners by priority', () => {
    sortAssetData(announcement, assetNames.ANNOUNCEMENT);
    expect(announcement).toStrictEqual(sorted_announcement);
  });
});

describe('stringToLiteral', () => {
  it('stringToLiteral function call with no url', () => {
    expect(stringToLiteral()).toBe('');
  });

  it('stringToLiteral function call with empty url', () => {
    const url = '';
    expect(stringToLiteral(url)).toBe(url);
  });

  it('stringToLiteral function call with valid external url', () => {
    const rzp_user = {
      user: {
        id: 'HpP3cspZ3AcuV2',
      },
    };
    const url = `www.razorpay.com/mid=${rzp_user.user.id}`;
    expect(stringToLiteral(url)).toBe('www.razorpay.com/mid=HpP3cspZ3AcuV2');
  });

  it('stringToLiteral function call with valid internal url', () => {
    const rzp_user = {
      user: {
        id: 'HpP3cspZ3AcuV2',
      },
    };
    const url = `dashboard/mid=${rzp_user.user.id}`;
    expect(stringToLiteral(url)).toBe('dashboard/mid=HpP3cspZ3AcuV2');
  });
});
