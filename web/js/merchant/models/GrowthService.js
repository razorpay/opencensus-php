import GenericEntity from './GenericEntity';
import store from 'merchant/store';

export default class GrowthService extends GenericEntity {
  resourceUrl = 'growth/assets';
  user = store.getState().session.user;

  /*

  Structure:
  routeName: {
    environmentType: 'channenID'
  }

  */
  routeToChannelIDMap = {
    default: {
      beta: 'HTdu8cC7FJEIHC',
      stage: 'HTdu8cC7FJEIHC',
      production: 'HpP3cspZ3AcuV2',
    },
    home: {
      beta: 'HTdu8cC7FJEIHC',
      stage: 'HTdu8cC7FJEIHC',
      production: 'HpP3cspZ3AcuV2',
    },
  };

  assetNames = {
    ANNOUNCEMENT: 'ANNOUNCEMENT',
  };

  getChannelID = (fromWhere = 'home') => {
    const channelID =
      this.routeToChannelIDMap[fromWhere]?.[window.APP_ENV] ||
      this.routeToChannelIDMap.default[window.APP_ENV] ||
      this.routeToChannelIDMap.default.production;

    return channelID;
  };

  fetchAssetData = (merchant_id, channel_id, assetName) => {
    return this.makeGenericAjaxCall({
      data: {
        merchant_id,
        channel_id,
        asset: assetName,
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
    const announcements = [];

    if (window.old_notifications) announcements.push(...window.old_notifications);
    if (this.user.isGrowthServiceEnabled) {
      const new_announcements = await this.fetchAssetData(
        merchant_id,
        this.getChannelID(fromWhere),
        this.assetNames.ANNOUNCEMENT,
      );

      if (new_announcements) announcements.push(...new_announcements);
      else if (window.new_notifications) announcements.push(...window.new_notifications);
    } else if (window.new_notifications) announcements.push(...window.new_notifications);

    if (announcements.length > 1) {
      announcements.sort((first, second) => {
        if (first.id === 'projectNitro') return -1;
        if (first.id === 'whats-new-JUL21-RXCC-ULTRA' && second.id !== 'projectNitro') return -1;
        if (second.id === 'projectNitro') return 1;
        if (second.id === 'whats-new-JUL21-RXCC-ULTRA') return 1;
        return second.start_ts - first.start_ts;
      });
    }

    return announcements;
  };
}
