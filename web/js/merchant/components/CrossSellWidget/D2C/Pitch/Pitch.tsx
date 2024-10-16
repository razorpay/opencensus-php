import React, { useEffect } from 'react';
import { Box } from '@razorpay/blade/components';

import AnimatedBarGraph from 'merchant/widgets/AnimatedBarGraph';

import BackButton from './BackButton';
import ProblemContent from './ProblemContent';
import SolutionContent from './SolutionContent';
import { useGetD2CPitchData } from './hooks';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { UCS_SERVICE_NAME } from 'merchant/widgets/utils';
import { useSteppedSplitPaneContext } from 'merchant/widgets/SteppedSplitPane/ context';
import { getCombinedTitleForAnalytics } from '../utils';
import { ANALYTICS_EXPERIMENT_NAME } from '../../constants';

function Pitch({ components }) {
  const {
    backgoundImage,
    shouldShowBackButton,
    pitchingProblemComponent,
    pitchingProblemChartData,
    barGraphVariant,
    barLabels,
    graphValue,
    selectedViewId,
    pitchingProblemCrossSellWidgetData,
    pitchingSolutionComponent,
    pitchingSolutionCrossSellWidgetData,
    goBackToProblemComponent,
    goToSolutionComponent,
    redirectToProductUrl,
  } = useGetD2CPitchData(components);

  const {
    screen,
    selectedStepIndex,
    selectedStepTitle,
    suggestedProduct,
    pitchingType,
    isWidgetVisibleOnce,
  } = useSteppedSplitPaneContext();

  useEffect(() => {
    if (
      screen &&
      selectedStepIndex &&
      selectedStepTitle &&
      suggestedProduct &&
      pitchingType &&
      isWidgetVisibleOnce
    ) {
      let objectName = '';
      let title = '';
      if (selectedViewId === pitchingProblemComponent.id) {
        objectName = 'Product Recommendation Insights Page';
        title = getCombinedTitleForAnalytics(pitchingProblemCrossSellWidgetData);
      } else if (selectedViewId === pitchingSolutionComponent.id) {
        objectName = 'Product Recommendation Solutions Page';
        title = getCombinedTitleForAnalytics(pitchingSolutionCrossSellWidgetData);
      }
      analyticsTrack({
        objectName,
        actionName: 'Loaded',
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
          title,
        },
      });
    }
  }, [
    selectedViewId,
    screen,
    selectedStepIndex,
    selectedStepTitle,
    suggestedProduct,
    pitchingType,
    isWidgetVisibleOnce,
  ]);

  return (
    <Box
      borderRadius="large"
      padding="spacing.0"
      borderColor="surface.border.gray.muted"
      borderWidth="thin"
      display="flex"
      flex="1"
      position="relative"
      backgroundImage={`url(${backgoundImage})`}
      backgroundRepeat="no-repeat"
      backgroundSize="cover"
      backgroundPosition="center"
      minHeight="500px"
      testID="d2c-pitch-component"
    >
      <BackButton
        shouldShowBackButton={shouldShowBackButton}
        onBackButtonClick={goBackToProblemComponent}
      />
      <AnimatedBarGraph
        variant={barGraphVariant}
        barLabels={barLabels}
        graphLabel={pitchingProblemChartData.data[0].label}
        graphValue={graphValue}
      />
      <Box
        width="100%"
        display="flex"
        alignItems="center"
        paddingLeft="spacing.9"
        position="relative"
      >
        <ProblemContent
          pitchingProblemComponent={pitchingProblemComponent}
          selectedViewId={selectedViewId}
          pitchingProblemCrossSellWidgetData={pitchingProblemCrossSellWidgetData}
          actionClickHandler={goToSolutionComponent}
        />
        <SolutionContent
          pitchingSolutionComponent={pitchingSolutionComponent}
          selectedViewId={selectedViewId}
          pitchingSolutionCrossSellWidgetData={pitchingSolutionCrossSellWidgetData}
          actionClickHandler={redirectToProductUrl}
        />
      </Box>
    </Box>
  );
}

export default Pitch;
