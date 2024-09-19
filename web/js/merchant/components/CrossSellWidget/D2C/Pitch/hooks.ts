import React from 'react';

import { BarGraphVariant } from 'merchant/widgets/AnimatedBarGraph/types';

import {
  D2C_WIDGET_ACTION_TYPES,
  D2C_WIDGET_COMPONENT_TYPES,
  D2C_WIDGET_STEP_TYPES,
} from '../constants';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { UCS_SERVICE_NAME } from 'merchant/widgets/utils';
import { useSteppedSplitPaneContext } from 'merchant/widgets/SteppedSplitPane/ context';
import { getCombinedTitleForAnalytics } from '../utils';
import { ANALYTICS_EXPERIMENT_NAME } from '../../constants';

export const useGetD2CPitchData = (components) => {
  const { screen, selectedStepIndex, selectedStepTitle, suggestedProduct, pitchingType } =
    useSteppedSplitPaneContext();

  const pitchingProblemComponent = components.find(
    (component) => component.type === D2C_WIDGET_STEP_TYPES.D2C_PRODUCT_PITCHING,
  );
  const pitchingProblemVisualDetails = pitchingProblemComponent.components.find(
    (component) => component.type === D2C_WIDGET_COMPONENT_TYPES.D2C_VISUALS,
  );
  const pitchingProblemChartData = pitchingProblemVisualDetails.data.chart_data;
  const pitchingProblemContent = pitchingProblemComponent.components.find(
    (component) => component.type === D2C_WIDGET_COMPONENT_TYPES.D2C_CARD_WIDGET,
  );
  const pitchingProblemCrossSellWidgetData = pitchingProblemContent.data.cross_sell_widget_data;
  const pitchingSolutionComponent = components.find(
    (component) => component.type === D2C_WIDGET_STEP_TYPES.D2C_SOLUTION_PITCHING,
  );
  const pitchingSolutionVisualDetails = pitchingSolutionComponent.components.find(
    (component) => component.type === D2C_WIDGET_COMPONENT_TYPES.D2C_VISUALS,
  );
  const pitchingSolutionChartData = pitchingSolutionVisualDetails.data.chart_data;
  const pitchingSolutionContent = pitchingSolutionComponent.components.find(
    (component) => component.type === D2C_WIDGET_COMPONENT_TYPES.D2C_CARD_WIDGET,
  );
  const pitchingSolutionCrossSellWidgetData = pitchingSolutionContent.data.cross_sell_widget_data;

  const [selectedViewId, setSelectedViewId] = React.useState(pitchingProblemComponent.id);
  const [barGraphVariant, setBarGraphVariant] = React.useState<BarGraphVariant>(
    pitchingProblemChartData.type,
  );
  const [barLabels, setBarLabels] = React.useState(pitchingProblemChartData.labels);
  const [graphValue, setGraphValue] = React.useState(
    pitchingProblemChartData.data[0].points?.[0]?.x,
  );
  const [backgoundImage, setBackgroundImage] = React.useState(
    pitchingProblemComponent.background_img,
  );

  const shouldShowBackButton = Number(selectedViewId) > pitchingProblemComponent.id;

  const goBackToProblemComponent = () => {
    analyticsTrack({
      objectName: 'Back Button',
      actionName: 'Clicked',
      screen,
      properties: {
        ...getCommonAnalyticsProperties(window.rzp_user, { addUserProperties: true }),
        version: 'v2',
        service: UCS_SERVICE_NAME.toLowerCase(),
        page: screen,
        tab_rank: selectedStepIndex,
        option_name: selectedStepTitle,
        experiment_name: ANALYTICS_EXPERIMENT_NAME,
        session_id: window?.session_id || '',
        suggested_product: suggestedProduct,
        type_of_pitch: pitchingType,
        title: getCombinedTitleForAnalytics(pitchingSolutionCrossSellWidgetData),
      },
    });

    setSelectedViewId(pitchingProblemComponent.id);
    setBarGraphVariant(pitchingProblemChartData.type);
    setBarLabels(pitchingProblemChartData.labels);
    setGraphValue(pitchingProblemChartData.data[0].points?.[0]?.x);
    setBackgroundImage(pitchingProblemComponent.background_img);
  };

  const goToSolutionComponent = () => {
    analyticsTrack({
      objectName: 'Product Recommendation See How Button',
      actionName: 'Clicked',
      screen,
      properties: {
        ...getCommonAnalyticsProperties(window.rzp_user, { addUserProperties: true }),
        version: 'v2',
        service: UCS_SERVICE_NAME.toLowerCase(),
        page: screen,
        tab_rank: selectedStepIndex,
        option_name: selectedStepTitle,
        experiment_name: ANALYTICS_EXPERIMENT_NAME,
        session_id: window?.session_id || '',
        suggested_product: suggestedProduct,
        type_of_pitch: pitchingType,
        title: getCombinedTitleForAnalytics(pitchingProblemCrossSellWidgetData),
      },
    });

    setSelectedViewId(pitchingSolutionComponent.id);
    setBarGraphVariant(pitchingSolutionChartData.type);
    setBarLabels(pitchingSolutionChartData.labels);
    setGraphValue(pitchingSolutionChartData.data[0].points?.[0]?.x);
    setBackgroundImage(pitchingSolutionComponent.background_img);
  };

  const redirectToProductUrl = () => {
    const redirectionUrl = pitchingSolutionCrossSellWidgetData?.actions?.action;
    if (
      pitchingSolutionCrossSellWidgetData.actions.type ===
        D2C_WIDGET_ACTION_TYPES.URL_REDIRECTION &&
      redirectionUrl
    ) {
      analyticsTrack({
        objectName: 'Product Recommendation Solutions Button',
        actionName: 'Clicked',
        screen,
        properties: {
          ...getCommonAnalyticsProperties(window.rzp_user, { addUserProperties: true }),
          version: 'v2',
          service: UCS_SERVICE_NAME.toLowerCase(),
          page: screen,
          tab_rank: selectedStepIndex,
          option_name: selectedStepTitle,
          experiment_name: ANALYTICS_EXPERIMENT_NAME,
          session_id: window?.session_id || '',
          suggested_product: suggestedProduct,
          type_of_pitch: pitchingType,
          title: getCombinedTitleForAnalytics(pitchingSolutionCrossSellWidgetData),
          redirection_url: redirectionUrl,
        },
      });
      window.open(redirectionUrl, '_blank');
    }
  };

  return {
    pitchingProblemComponent,
    pitchingProblemVisualDetails,
    pitchingProblemChartData,
    pitchingProblemContent,
    pitchingProblemCrossSellWidgetData,
    pitchingSolutionComponent,
    pitchingSolutionVisualDetails,
    pitchingSolutionChartData,
    pitchingSolutionContent,
    pitchingSolutionCrossSellWidgetData,
    selectedViewId,
    setSelectedViewId,
    barGraphVariant,
    setBarGraphVariant,
    barLabels,
    setBarLabels,
    graphValue,
    setGraphValue,
    backgoundImage,
    setBackgroundImage,
    shouldShowBackButton,
    goBackToProblemComponent,
    goToSolutionComponent,
    redirectToProductUrl,
  };
};
