import { BoxProps } from '@razorpay/blade/components';
import { QueryKey } from '@tanstack/query-core';

export interface LayoutWidgetProps {
  queryKey: QueryKey;
  isLoading: boolean;
  properties?: {
    variant?: BoxProps['display'];
  };
  components: React.ReactNode[];
  styles: BoxProps;
}
