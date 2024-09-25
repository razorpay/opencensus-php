import { BoxProps } from '@razorpay/blade/components';

export interface CarouselWidgetProps {
  type: string;
  title: string;
  background_img?: string;
  components: Array<{ type: string; id: string; styles?: Record<string, any> }>;
  styles?: {
    width?: BoxProps['width'];
    inherited_styles?: {
      carousel_product_card?: {
        width?: BoxProps['width'];
        height?: BoxProps['height'];
        border_radius?: BoxProps['borderRadius'];
        toggle_responsive?: boolean;
        show_on_hover?: boolean;
      };
    };
  };
}
