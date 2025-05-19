import React from 'react';
import { Badge } from '@razorpay/blade/components';
import type { TitleSuffixConfig, BadgeConfig } from './Products';

export const getTitleSuffixComponent = (
  config?: TitleSuffixConfig | BadgeConfig | React.ReactElement,
): React.ReactElement | undefined => {
  if (!config) return undefined;


  if ('componentType' in config) {
    switch (config.componentType) {
      case 'Badge':
        return <Badge {...config.props} />;
      default:
        return undefined;
    }
  }

  return undefined;
};
