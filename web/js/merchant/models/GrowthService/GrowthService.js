import GenericEntity from '../GenericEntity';
import { getMode, getUser } from 'merchant/store';
import { assetNames } from './data';
import { getChannelID, sortAssetData, isValidAssetData } from './commonUtils';

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

  fetchAssetData = (merchant_id, channel_id, assetName) => {
    return this.makeGenericAjaxCall({
      data: {
        merchant_id,
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
            assetData.push({ ...template.data, ...assetEntry.tracking_data });
          });
        });
        return assetData;
      })
      .catch((_) => null);
  };

  getAnnouncements = async (merchant_id, fromWhere) => {
    let announcements = [];

    if (Array.isArray(window.old_notifications)) announcements.push(...window.old_notifications);
    if (this.user.isGrowthServiceEnabled) {
      const new_announcements = await this.fetchAssetData(
        merchant_id,
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
}
