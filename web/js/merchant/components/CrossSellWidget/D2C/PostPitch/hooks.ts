import { D2C_WIDGET_COMPONENT_TYPES } from '../constants';

export const useGetD2CPostPitchData = (components) => {
  const postPitchComponent = components[0];

  const postPitchVisualDetails = postPitchComponent.components.find(
    (component) => component.type === D2C_WIDGET_COMPONENT_TYPES.D2C_VISUALS,
  );

  const postPitchChartData = postPitchVisualDetails.data.chart_data;

  const postPitchContent = postPitchComponent.components.find(
    (component) => component.type === D2C_WIDGET_COMPONENT_TYPES.D2C_CARD_WIDGET,
  );

  const postPitchCrossSellWidgetData = postPitchContent.data.cross_sell_widget_data;

  return {
    postPitchComponent,
    postPitchChartData,
    postPitchCrossSellWidgetData,
  };
};
