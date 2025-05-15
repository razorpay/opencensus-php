import { LinkProps } from '@razorpay/blade/components';

import { TrackParameters } from 'merchant/widgets/types';

export interface LinkWidgetProps {
  title: string;
  action?: string;
  icon: string;
  icon_position: 'right' | 'left';
  color?: LinkProps['color'];
  properties?: {
    variant?: LinkProps['variant'];
  };
  action_params?: Record<string, any>;
  analyticsProperties?: TrackParameters;
}
