import { getItem } from 'common/utils/localStorage';
import GenericEntity from 'merchant/models/GenericEntity';
import { getMode, getUser } from 'merchant/store';

import { getChannelID, sortAssetData, isValidAssetData, sortCarouselBanner } from './commonUtils';
import { assetNames, namespace } from './data';

export default class GrowthService extends GenericEntity {
  resourceUrl = 'growth/assets';
  user = getUser();

  constructor() {
    super();
    if (!this?.user?.current && window.rzp_user?.current) this.user = window.rzp_user;
  }

  getUserFeatures = () => {
    let device, browser, features;
    const mode = getMode() || undefined;
    const role = this.user?.userRole || undefined;

    if (typeof window.razorpayAnalytics?.utils?.getBrowserDetails === 'function') {
      const browserDetails = window.razorpayAnalytics.utils.getBrowserDetails();
      device = browserDetails?.device || undefined;
      browser = browserDetails?.browser || undefined;
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
    const corp_id = this.user?.merchant?.org_id || '';
    const user_id = this.user?.user?.id;

    return {
      corp_id,
      user_id,
      ...this.getUserFeatures(),
    };
  };

  removeDismissedData = (data = {}) => {
    const bannerKey = `${data?.id}-${this.user?.current}`;
    const val = getItem(bannerKey);
    return !val; // return 'false' if key/value is 'null' & not dismissed by user
  };

  fetchAssetData = (channel_id, assetName, dynamicAssets, channelDetail) => {
    if (!this.user?.current) {
      return null;
    }
    return this.makeGenericAjaxCall({
      data: {
        merchant_id: this.user?.current,
        asset: assetName,
        channel_id,
        channel_detail: channelDetail,
        ...(dynamicAssets && { dynamic_asset_name: dynamicAssets }),
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
            Object.assign(assetEntry.tracking_data, {
              template_id: template.id,
              channel_id,
              channel_detail: channelDetail,
              asset: template.asset,
              dynamic_asset_name: dynamicAssets,
            });
            assetData.push({
              ...template.data,
              tracking_data: assetEntry.tracking_data,
            });
          });
        });
        return assetData;
      })
      .catch((_) => null);
  };

  fetchTemplateDataById = (template_id) => {
    this.resourceUrl = `growth/template/${template_id}`;
    return this.makeGenericAjaxCall({
      method: 'get',
      mode: 'live',
      headers: {
        'Content-Type': 'application/json',
      },
    })
      .then((response) => {
        return response?.data?.response?.template?.data;
      })
      .catch((_) => null);
  };

  getAnnouncements = async (fromWhere) => {
    let announcements = [];

    const new_announcements = await this.fetchAssetData(
      getChannelID(fromWhere, this.user.isOrgRZP),
      assetNames.ANNOUNCEMENT,
    );

    if (Array.isArray(new_announcements)) announcements.push(...new_announcements);

    announcements = announcements.filter((announcement) =>
      isValidAssetData(announcement, assetNames.ANNOUNCEMENT),
    );

    sortAssetData(announcements, assetNames.ANNOUNCEMENT);

    return announcements;
  };

  getBanners = async (fromWhere) => {
    const totalBannersLimit = 1;
    let banners = [];

    const channelDetail = {
      namespace,
      route: fromWhere,
    };
    const gsBanners = this.user?.isOrgRZP
      ? await this.fetchAssetData(undefined, assetNames.BANNER, undefined, channelDetail)
      : await this.fetchAssetData(getChannelID(fromWhere, this.user?.isOrgRZP), assetNames.BANNER);

    if (Array.isArray(gsBanners)) banners.push(...gsBanners);

    banners = banners.filter(
      (banner) => isValidAssetData(banner, assetNames.BANNER) && this.removeDismissedData(banner),
    );
    sortAssetData(banners, assetNames.BANNER);
    if (banners.length) banners = banners.slice(0, totalBannersLimit);
    return banners;
  };
  getPricingSubscription = async (fromWhere) => {
    const gsPricingSub =
      (await this.fetchAssetData(
        getChannelID(fromWhere, this.user?.isOrgRZP),
        assetNames.JSON_SCHEMA,
        'pricing_bundle',
      )) || [];
    const validGsPricingSub = gsPricingSub.filter((pricingBundle) =>
      isValidAssetData(pricingBundle, assetNames.PRICING_BUNDLE),
    );
    return validGsPricingSub;
  };

  getExclusiveOfferModal = async (fromWhere) => {
    let exclusive_offers = {};

    try {
      const gsExclusiveOffer = await this.fetchAssetData(
        getChannelID(fromWhere, this.user?.isOrgRZP),
        assetNames.EXCLUSIVE_OFFER,
      );

      if (Array.isArray(gsExclusiveOffer) && gsExclusiveOffer.length > 0) {
        exclusive_offers = gsExclusiveOffer[0];
      }
    } catch (e) {
      if (window.APP_ENV !== 'production') console.error(e);
    }

    return exclusive_offers;
  };
  getCarouselBanners = async (fromWhere) => {
    let carouselBanner =
      (await this.fetchAssetData(
        getChannelID(fromWhere, this.user?.isOrgRZP),
        assetNames.BANNER_CAROUSEL_ITEM,
      )) || [];
    if (carouselBanner.length > 5) {
      carouselBanner = [...carouselBanner.slice(0, 5)];
    }
    return sortCarouselBanner(carouselBanner);
  };
  getGSModal = async (template_id) => {
    const gs_modal = await this.fetchTemplateDataById(template_id);
    if (gs_modal !== null) {
      return gs_modal;
    }
    return {};
  };

  getXBankingWidget = async (fromWhere) => {
    const xBankingWidget = await this.fetchAssetData(
      getChannelID(fromWhere, this.user.isOrgRZP),
      assetNames.X_BANKING_WIDGET,
    );
    return xBankingWidget;
  };
}
