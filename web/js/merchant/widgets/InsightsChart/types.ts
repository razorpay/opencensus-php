import { DateRangeInput } from 'merchant/widgets/common/Select/types';
import { CommonWidgetProps } from 'merchant/widgets/types';

export interface InsightsChartProps extends CommonWidgetProps {
  title: string;
  components: Array<{ type: string; id: string }>;
  inputs: Array<DateRangeInput>;
  type: string;
}

export interface InsightsChartLoadingProps {
  title: string;
  components: Array<{ type: string; id: string }>;
  inputs: Array<DateRangeInput>;
}
