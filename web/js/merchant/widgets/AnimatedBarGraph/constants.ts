export const BAR_VARIANTS = {
  NEUTRAL: 'neutral',
  POSITIVE: 'positive',
  NEGATIVE: 'negative',
} as const;

//Naming Convention: [type of first bar]_[type of second bar]_[change in trend from first to second]
export const BAR_GRAPH_VARIANTS = {
  NEUTRAL_POSITIVE_INCREASE: 'neutral_positive_increase',
  NEUTRAL_POSITIVE_DECREASE: 'neutral_positive_decrease',
  NEUTRAL_NEGATIVE_INCREASE: 'neutral_negative_increase',
  NEUTRAL_NEGATIVE_DECREASE: 'neutral_negative_decrease',
} as const;
