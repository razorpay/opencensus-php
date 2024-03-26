import { LinkWidgetProps } from 'merchant/widgets/common/Link/types';

export interface ProductCardWidgetProps {
  title: string;
  description: string;
  background_img: string;
  actions: Array<LinkWidgetProps>;
  id: string;
  type: string;
}
