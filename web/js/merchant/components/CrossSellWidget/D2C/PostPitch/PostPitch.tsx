import { Box } from '@razorpay/blade/components';
import React, { useEffect } from 'react';
import ProductInsight from '../../ProductInsight';
import AnimatedBarGraph from 'merchant/widgets/AnimatedBarGraph';
import { useGetD2CPostPitchData } from './hooks';
import { analyticsTrack } from 'common/utils/analytics';
import { useSteppedSplitPaneContext } from 'merchant/widgets/SteppedSplitPane/ context';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { UCS_SERVICE_NAME } from 'merchant/widgets/utils';
import { getCombinedTitleForAnalytics, getTypeOfPostPitchTab } from '../utils';
import { ANALYTICS_EXPERIMENT_NAME } from '../../constants';

function PostPitch({ components }) {
  const { postPitchCrossSellWidgetData, postPitchComponent, postPitchChartData } =
    useGetD2CPostPitchData(components);

  const { screen, selectedStepIndex, selectedStepTitle, suggestedProduct, pitchingType } =
    useSteppedSplitPaneContext();

  useEffect(() => {
    if (screen && selectedStepIndex && selectedStepTitle && suggestedProduct && pitchingType) {
      analyticsTrack({
        objectName: 'Product Recommendation Insights Page',
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
          title: getCombinedTitleForAnalytics(postPitchCrossSellWidgetData),
          type_of_tab: getTypeOfPostPitchTab(postPitchComponent),
        },
      });
    }
  }, [screen, selectedStepIndex, selectedStepTitle, suggestedProduct, pitchingType]);

  return (
    <Box
      borderRadius="large"
      padding="spacing.0"
      borderColor="surface.border.gray.muted"
      borderWidth="thin"
      display="flex"
      flex="1"
      position="relative"
      flexDirection="column"
      backgroundImage={`url(${postPitchComponent.background_img})`}
      backgroundRepeat="no-repeat"
      backgroundSize="cover"
      backgroundPosition="center"
      minHeight="500px"
      testID="d2c-post-pitch-component"
    >
      <Box textAlign="center" paddingTop="spacing.7">
        <ProductInsight
          displayText={postPitchCrossSellWidgetData.text_content.display}
          displayColor={postPitchCrossSellWidgetData.text_content?.display_color}
          aboveDisplayHeading={postPitchCrossSellWidgetData.text_content.above_display_heading}
          belowDisplayHeading={postPitchCrossSellWidgetData.text_content.below_display_heading}
          postPitchCaption={postPitchCrossSellWidgetData.text_content.caption}
        />
      </Box>
      <Box flex="1">
        <AnimatedBarGraph
          variant={postPitchChartData.type}
          isFullWidth
          barLabels={postPitchChartData.labels}
          graphLabel={postPitchChartData.data[0].label}
          graphValue={postPitchChartData.data[0].points?.[0]?.x}
        />
      </Box>
    </Box>
  );
}

export default PostPitch;
