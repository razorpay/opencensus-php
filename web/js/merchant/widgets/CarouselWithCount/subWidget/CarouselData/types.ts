import { LinkWidgetProps } from 'merchant/widgets/common/Link/types';
import { CommonWidgetProps } from 'merchant/widgets/types';

export interface CarouselDataWidgetProps extends CommonWidgetProps {
  title: string;
  description: string;
  variant: string;
  action?: LinkWidgetProps & { type: string };
  id: string;
  type: string;
}
