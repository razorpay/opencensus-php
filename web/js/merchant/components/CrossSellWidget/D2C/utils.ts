import { D2C_WIDGET_STEP_TYPES } from './constants';

export const getCombinedTitleForAnalytics = (crossSellWidgetData) => {
  const display = crossSellWidgetData.text_content?.display || '';
  const aboveDisplayHeading = crossSellWidgetData.text_content?.above_display_heading || '';
  const belowDisplayHeading = crossSellWidgetData.text_content?.below_display_heading || '';

  return `${aboveDisplayHeading} ${display} ${belowDisplayHeading}`;
};

export const getTypeOfPostPitchTab = (postPitchComponent) => {
  const { type } = postPitchComponent;

  if (type === D2C_WIDGET_STEP_TYPES.D2C_COOLING_PERIOD) {
    return 'generic_value_realization';
  } else if (type === D2C_WIDGET_STEP_TYPES.D2C_VALUE_REALIZATION) {
    return 'value_realization';
  }
};
