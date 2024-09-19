import { Theme } from '@razorpay/blade/components';

import { BAR_GRAPH_VARIANTS, BAR_VARIANTS } from './constants';

// eslint-disable-next-line prettier/prettier
export type BarVariant = (typeof BAR_VARIANTS)[keyof typeof BAR_VARIANTS];

// eslint-disable-next-line prettier/prettier
export type BarGraphVariant = (typeof BAR_GRAPH_VARIANTS)[keyof typeof BAR_GRAPH_VARIANTS];

export type BarLabelAlignment = 'left' | 'right';

export type Height = 'low' | 'high';

export interface BarProps {
  variant: BarVariant;
  height: Height;
  labelAlignment: BarLabelAlignment;
  showChangeIndicator: boolean;
  barLabel: string | null;
  barValue?: string;
}

export interface StyledBarProps {
  variant: BarVariant;
  height: Height;
  theme: Theme;
}

export type IndicatorColorText =
  | 'feedback.text.neutral.intense'
  | 'feedback.text.positive.intense'
  | 'feedback.text.negative.intense';

export type IndicatorColorIcon =
  | 'feedback.icon.neutral.intense'
  | 'feedback.icon.positive.intense'
  | 'feedback.icon.negative.intense';
