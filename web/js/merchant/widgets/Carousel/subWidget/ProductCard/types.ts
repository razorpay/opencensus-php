import { BoxProps } from '@razorpay/blade/components';

import { ButtonWidgetProps } from 'merchant/widgets/common/Button/types';
import { LinkWidgetProps } from 'merchant/widgets/common/Link/types';

export interface ProductCardWidgetProps {
  id: string;
  type: string;
  title: string;
  description: string;
  actions: Array<LinkWidgetProps | ButtonWidgetProps>;
  background_img: string;
  styles?: {
    width?: BoxProps['width'];
    height?: BoxProps['height'];
    border_radius?: BoxProps['borderRadius'];
    toggle_responsive?: boolean;
    show_on_hover?: boolean;
  };
}

export interface ProductCardWidgetLoaderProps {
  styles?: ProductCardWidgetProps['styles'];
}
