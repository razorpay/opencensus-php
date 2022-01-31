import GenericEntity from '../GenericEntity';
import { getMode, getUser } from 'merchant/store';
import { assetNames } from './data';
import { getChannelID, sortAssetData, isValidAssetData, sortCarouselBanner } from './commonUtils';

export default class GrowthService extends GenericEntity {
  resourceUrl = 'growth/assets';
  user = getUser();

  getUserFeatures = () => {
    let device, browser, features;
    const mode = getMode();
    const role = this.user?.userRole;

    if (typeof window.razorpayAnalytics?.utils?.getBrowserDetails === 'function') {
      const browserDetails = window.razorpayAnalytics.utils.getBrowserDetails();
      device = browserDetails?.device;
      browser = browserDetails?.browser;
    }

    const context = {
      device,
      mode,
      role,
      browser,
    };

    if (Array.isArray(this.user?.features))
      features = this.user.features.filter(({ value }) => value).map(({ feature }) => feature);

    return {
      context,
      features,
    };
  };

  getPayloadData = () => {
    const { org_id: corp_id = '' } = this.user?.merchant;
    const user_id = this.user?.user?.id;

    return {
      corp_id,
      user_id,
      ...this.getUserFeatures(),
    };
  };

  fetchAssetData = (channel_id, assetName) => {
    return this.makeGenericAjaxCall({
      data: {
        merchant_id: this.user?.current,
        channel_id,
        asset: assetName,
        ...this.getPayloadData(),
      },
      method: 'post',
      mode: 'live',
      headers: {
        'Content-Type': 'application/json',
      },
    })
      .then((response) => {
        const assetData = [];

        response.data.response.asset_data.forEach((assetEntry) => {
          assetEntry.templates.forEach((template) => {
            assetData.push({ ...template.data, tracking_data: assetEntry.tracking_data });
          });
        });
        return assetData;
      })
      .catch((_) => null);
  };

  getAnnouncements = async (fromWhere) => {
    let announcements = [];

    if (Array.isArray(window.old_notifications) && this.user.isOrgRZP)
      announcements.push(...window.old_notifications);
    if (this.user.isGSAnnouncementsEnabled) {
      const new_announcements = await this.fetchAssetData(
        getChannelID(fromWhere, this.user.isOrgRZP),
        assetNames.ANNOUNCEMENT,
      );

      if (Array.isArray(new_announcements)) announcements.push(...new_announcements);
      else if (Array.isArray(window.new_notifications))
        announcements.push(...window.new_notifications);
    } else if (Array.isArray(window.new_notifications))
      announcements.push(...window.new_notifications);

    announcements = announcements.filter((announcement) =>
      isValidAssetData(announcement, assetNames.ANNOUNCEMENT),
    );

    sortAssetData(announcements, assetNames.ANNOUNCEMENT);

    return announcements;
  };

  getBanners = async (fromWhere) => {
    const totalBannersLimit = 1;
    let banners = [];

    if (this.user.isGSBannersEnabled) {
      const gsBanners = await this.fetchAssetData(
        getChannelID(fromWhere, this.user.isOrgRZP),
        assetNames.BANNER,
      );

      if (Array.isArray(gsBanners)) banners.push(...gsBanners);
    }

    banners = banners.filter((banner) => isValidAssetData(banner, assetNames.BANNER));
    sortAssetData(banners, assetNames.BANNER);
    if (banners.length) banners = banners.slice(0, totalBannersLimit);

    return banners;
  };

  getExclusiveOfferModal = async (fromWhere) => {
    let exclusive_offers = {};

    const gsExclusiveOffer = await this.fetchAssetData(
      getChannelID(fromWhere, this.user.isOrgRZP),
      assetNames.EXCLUSIVE_OFFER,
    );

    if (Array.isArray(gsExclusiveOffer) && gsExclusiveOffer.length > 0) {
      exclusive_offers = gsExclusiveOffer[0];
    }

    return exclusive_offers;
  };
  getCarouselBanners = async (fromWhere) => {
    let carouselBanner =
      (await this.fetchAssetData(
        getChannelID(fromWhere, this.user.isOrgRZP),
        assetNames.BANNER_CAROUSEL_ITEM,
      )) || [];
    if (carouselBanner.length > 5) {
      carouselBanner = [...carouselBanner.slice(0, 5)];
    }
    return sortCarouselBanner(carouselBanner);
  };
}
