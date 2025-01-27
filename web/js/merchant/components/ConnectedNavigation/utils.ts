import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

import { Mode } from './types';
import { ANALYTICS_ONENAV } from '../NavigationLayout/constants';

type GetCurrentModeType = {
  mode: Mode;
  partnerMode: Mode;
  selectedProduct: 'payments_top_navigation_item' | 'partners_top_navigation_item';
};
interface TrackProfileClick {
  objectName: 'Profile Options' | 'L0 Main Frame Icons';
  optionName: string;
  bu_title: string;
  type: 'icon' | 'option'; // Assuming type can be 'icon' or other values
}

export const getCurrentMode = ({ mode, partnerMode, selectedProduct }: GetCurrentModeType) => {
  if (selectedProduct === 'payments_top_navigation_item') {
    return mode;
  }
  if (selectedProduct === 'partners_top_navigation_item') {
    return partnerMode;
  }
  return mode;
};

export const trackProfileDropdownClicks = ({
  objectName,
  optionName,
  bu_title,
  type,
}: TrackProfileClick): void => {
  const analyticsInfo = {
    objectName,
    actionName: 'Clicked',
    screen: ANALYTICS_ONENAV.SCREEN,
    properties: {
      ...getCommonAnalyticsProperties(window.rzp_user, { addUserProperties: true }),
      version: 'v1',
      option_name: optionName,
      page: location.pathname?.replace('/app/', ''),
      bu_title,
      icon_name: type === 'icon' ? 'Profile' : null,
      experiment_name: ANALYTICS_ONENAV.EXPERIMENT_NAME,
    },
  };
  analyticsTrack(analyticsInfo);
};
