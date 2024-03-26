import { CommonWidgetProps } from 'merchant/widgets/types';
import { ChartDataType } from '../InsightItem/types';
import { LinkWidgetProps } from 'merchant/widgets/common/Link/types';
import { DateRangeValues } from 'merchant/widgets/common/Select/types';

export interface DoughnutWidgetProps extends CommonWidgetProps {
  title: string;
  tooltip_text: string;
  type: string;
  analytics: {
    enabled: boolean;
  };
  data: {
    chart_data: ChartDataType;
  };
  action: LinkWidgetProps;
  date: DateRangeValues;
}
