import { ChangeType } from 'merchant/widgets/InsightsChart/subwidgets/InsightItem/types';
import { DateRangeInput, DateRangeValues } from 'merchant/widgets/common/Select/types';
import { ChartDataType } from 'merchant/widgets/common/types';
import { CommonWidgetProps } from 'merchant/widgets/types';

export type TabDataType = {
  value: number;
  value_type: string;
  currency: string;
  change: number;
  sub_text: string;
  chart_data: ChartDataType;
  change_type: ChangeType;
  change_behavior_inverted?: boolean;
};

export enum IconPositionEnum {
  LEFT = 'left',
  RIGHT = 'right',
}

export type TabActionType = {
  title: string;
  action: string;
  type: string;
  icon: string;
  icon_position: IconPositionEnum;
};

export type ComponentDataType = {
  id: string;
  title: string;
  data: TabDataType;
  action: TabActionType;
  tooltip_text: string;
  type: string;
};

export interface TabbedChartsProps extends CommonWidgetProps {
  id: string;
  type: string;
  title: string;
  background_img?: string;
  inputs: Array<DateRangeInput>;
  components: Array<ComponentDataType>;
}

export type TabsWrapperProps = {
  children: React.ReactElement<TabProps>[];
  analyticsProperties: any;
};

export type TabProps = {
  id: string;
  tabData: ComponentDataType;
  date: DateRangeValues;
  analyticsProperties: Record<string, any>;
};

export type TabCardProps = {
  isActive: boolean;
  tabData: ComponentDataType;
  cardPosition?: number;
};

export type GetTabbedChartDatasetType = {
  labels: string[];
  datasets: {
    label: string;
    data: number[];
    fill: boolean;
  }[];
};
