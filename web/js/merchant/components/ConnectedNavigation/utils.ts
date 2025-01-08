import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { ANALYTICS_ONENAV_EXPERIMENT } from '../NavigationLayout/constants';

interface TrackProfileClick {
  objectName: 'Profile Options' | 'L0 Main Frame Icons';
  optionName: string;
  bu_title: string;
  type: 'icon' | 'option'; // Assuming type can be 'icon' or other values
}

export const trackProfileDropdownClicks = ({
  objectName,
  optionName,
  bu_title,
  type,
}: TrackProfileClick): void => {
  let analyticsInfo = {
    objectName: objectName,
    actionName: 'Clicked',
    screen: location.pathname?.replace('/app/', ''),
    properties: {
      ...getCommonAnalyticsProperties(window.rzp_user, { addUserProperties: true }),
      version: 'v1',
      option_name: optionName,
      page: location.pathname?.replace('/app/', ''),
      bu_title: bu_title,
      icon_name: type === 'icon' ? 'Profile' : null,
      experiment_name: ANALYTICS_ONENAV_EXPERIMENT,
    },
  };
  analyticsTrack(analyticsInfo);
};
