import { ButtonProps } from '@razorpay/blade/components';

import { TrackParameters } from 'merchant/widgets/types';

export interface ButtonWidgetProps {
  title: string;
  action?: string;
  icon: string;
  icon_position: 'right' | 'left';
  properties?: {
    variant?: ButtonProps['variant'];
  };
  action_params?: Record<string, any>;
  analyticsProperties?: TrackParameters;
}
