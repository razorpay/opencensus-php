import { Theme } from '@razorpay/blade/components';

import { BUSINESS_PERFORMANCE_VIEW_BY_OPTIONS } from './constants';
import { BusinessPerformanceProps } from './types';

export function createGradientMap({ direction, theme }: { direction: string; theme: Theme }) {
  return {
    positive: `linear-gradient(${direction}, ${theme.colors.feedback.background.positive.subtle}, ${theme.colors.surface.background.gray.intense})`,
    negative: `linear-gradient(${direction}, ${theme.colors.feedback.background.negative.subtle}, ${theme.colors.surface.background.gray.intense})`,
  };
}

export function getPropsForInput(args: {
  isMobile: boolean;
  input: BusinessPerformanceProps['inputs'][number];
}) {
  switch (args.input.name) {
    case 'View By:':
      return {
        defaultValue: args.input.default_value,
        dropdownProps: {
          _width: args.isMobile ? '200px' : '300px',
          selectionType: 'single',
        },
        label: args.input.name,
        labelPosition: 'left',
        values: BUSINESS_PERFORMANCE_VIEW_BY_OPTIONS,
        // hide label on mobile
        ...(args.isMobile && { label: '' }),
      };
    default:
      return {};
  }
}
