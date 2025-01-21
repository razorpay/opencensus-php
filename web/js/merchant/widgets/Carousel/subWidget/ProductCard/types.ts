import { ButtonWidgetProps } from 'merchant/widgets/common/Button/types';
import { LinkWidgetProps } from 'merchant/widgets/common/Link/types';

export interface ProductCardWidgetProps {
  id: string;
  type: string;
  title: string;
  description: string;
  actions: Array<LinkWidgetProps | ButtonWidgetProps>;
  background_img: string;
}
