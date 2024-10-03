import React, { useEffect, useRef } from 'react';
import { Box, Card, CardBody } from '@razorpay/blade/components';

import ContentPaneLoader from 'merchant/components/CrossSellWidget/D2C/ContentPaneLoader';
import Pitch from 'merchant/components/CrossSellWidget/D2C/Pitch';
import PostPitch from 'merchant/components/CrossSellWidget/D2C/PostPitch';
import { D2C_WIDGET_STEP_TYPES } from 'merchant/components/CrossSellWidget/D2C/constants';
import SectionHeading from 'merchant/components/Home/SectionHeading';
import { isMobileDevice } from 'merchant/components/Home/data';

import StepSelector from './StepSelector';
import {
  getDefaultSelectedStepId,
  getPitchingTypeForSelectedStep,
  getSuggestedProductForSelectedStep,
} from './utils';
import { analyticsTrack } from 'common/utils/analytics';
import { getUcsAliasFromQueryKey, track, UCS_SERVICE_NAME } from '../utils';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { SteppedSplitPaneContext } from './ context';
import { useRetryWidget } from '../hooks';
import { ErrorState } from '../common/ErrorState';
import { ANALYTICS_EXPERIMENT_NAME } from 'merchant/components/CrossSellWidget/constants';

function SteppedSplitPane({
  isLoading,
  description,
  title,
  components,
  queryKey,
  error,
  type,
  id,
}): JSX.Element | null {
  const [isRetrying, retryHandler] = useRetryWidget(queryKey);

  const [selectedStepId, setSelectedStepId] = React.useState<string | null>(null);
  const [selectedStepIndex, setSelectedStepIndex] = React.useState<number | null>(null);
  const [selectedStepTitle, setSelectedStepTitle] = React.useState<string | null>(null);
  const [suggestedProduct, setSuggestedProduct] = React.useState<string | null>(null);
  const [pitchingType, setPitchingType] = React.useState<string | null>(null);

  const [isWidgetVisibleOnce, setIsWidgetVisibleOnce] = React.useState(false);

  const screen = getUcsAliasFromQueryKey(queryKey) ?? '';
  const widgetId = `merchantDashboard.${screen}.${type}.${id}`;

  const steppedSplitPaneRef = useRef(null);

  useEffect(() => {
    const observer = new IntersectionObserver(
      ([entry]) => {
        if (entry.isIntersecting && !isWidgetVisibleOnce) {
          const defaultSelectedStepId = getDefaultSelectedStepId(components);
          const defaultTabNumber =
            components.findIndex((step) => step.id === defaultSelectedStepId) + 1;

          analyticsTrack({
            objectName: 'Cross Sell Widget ',
            actionName: 'Displayed',
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
              default_tab_number: defaultTabNumber,
            },
          });

          setIsWidgetVisibleOnce(true);
        }
      },
      {
        root: null, // Use the viewport as the root
        rootMargin: '0px',
        threshold: 1, // Trigger when 10% of the component is visible
      },
    );

    if (steppedSplitPaneRef.current) {
      observer.observe(steppedSplitPaneRef.current);
    }

    return () => {
      if (steppedSplitPaneRef.current) {
        observer.unobserve(steppedSplitPaneRef.current);
      }
    };
  }, [isWidgetVisibleOnce, components]);

  useEffect(() => {
    setSelectedStepId(getDefaultSelectedStepId(components));
  }, [components]);

  useEffect(() => {
    if (selectedStepId) {
      const selectedStep = components.find((step) => step.id === selectedStepId);
      const stepIndex = components.findIndex((step) => step.id === selectedStepId);
      const suggestedProduct = getSuggestedProductForSelectedStep(selectedStep);
      const pitchingType = getPitchingTypeForSelectedStep(selectedStep);

      setSuggestedProduct(suggestedProduct);
      setPitchingType(pitchingType);
      setSelectedStepTitle(selectedStep?.title);
      setSelectedStepIndex(stepIndex + 1);
    }
  }, [selectedStepId]);

  useEffect(() => {
    if (!isLoading && !isRetrying) {
      const properties = {
        title,
        widgetId,
        actionBy: widgetId,
        ...(error ? { error: `${error.message}` } : { count: components.length }),
      };
      track({
        objectName: 'widget',
        actionName: error ? 'error' : 'loaded',
        screen,
        properties,
      });
    }
  }, [isLoading, isRetrying, error]);

  const handleStepSelectorClick = (stepId: string, stepIndex: number, stepTitle: string) => {
    return () => {
      if (stepId !== selectedStepId) {
        const selectedStep = components.find((step) => step.id === stepId);
        const suggestedProduct = getSuggestedProductForSelectedStep(selectedStep);
        const pitchingType = getPitchingTypeForSelectedStep(selectedStep);

        analyticsTrack({
          objectName: 'Product Recommendation Tab',
          actionName: 'Clicked',
          screen,
          properties: {
            ...getCommonAnalyticsProperties(window.rzp_user, { addUserProperties: true }),
            version: 'v2',
            service: UCS_SERVICE_NAME.toLowerCase(),
            page: screen,
            tab_rank: stepIndex,
            option_name: stepTitle,
            experiment_name: ANALYTICS_EXPERIMENT_NAME,
            session_id: window?.session_id || '',
            suggested_product: suggestedProduct,
          },
        });

        setSelectedStepId(stepId);
        setSelectedStepIndex(stepIndex);
        setSelectedStepTitle(stepTitle);
        setSuggestedProduct(suggestedProduct);
        setPitchingType(pitchingType);
      }
    };
  };

  const getPaneContent = () => {
    const selectedStep = components.find((step) => step.id === selectedStepId);
    const selectedStepType = selectedStep ? selectedStep.components[0].type : false;
    const selectedStepComponents = selectedStep ? selectedStep.components : [];

    if (isLoading || isRetrying || !selectedStepId) {
      return <ContentPaneLoader />;
    } else if (
      selectedStepType === D2C_WIDGET_STEP_TYPES.D2C_PRODUCT_PITCHING ||
      selectedStepType === D2C_WIDGET_STEP_TYPES.D2C_SOLUTION_PITCHING
    ) {
      return <Pitch components={selectedStepComponents} />;
    } else if (
      selectedStepType === D2C_WIDGET_STEP_TYPES.D2C_COOLING_PERIOD ||
      selectedStepType === D2C_WIDGET_STEP_TYPES.D2C_VALUE_REALIZATION
    ) {
      return <PostPitch components={selectedStepComponents} />;
    }

    throw new Error('UCS response does not contain a valid type');
  };

  //Hide on mobile devices for initial release
  if (isMobileDevice(1200)) {
    return null;
  }

  return (
    <SteppedSplitPaneContext.Provider
      value={{
        screen,
        selectedStepId,
        setSelectedStepId,
        selectedStepIndex,
        setSelectedStepIndex,
        selectedStepTitle,
        setSelectedStepTitle,
        suggestedProduct,
        pitchingType,
      }}
    >
      <Box marginX="spacing.6">
        <SectionHeading caption={description} heading={title} />
        <Card padding="spacing.0" marginTop="spacing.4">
          <CardBody>
            {error ? (
              <ErrorState
                backgroundColor="surface.background.gray.intense"
                text={`Widget couldn't be loaded`}
                retryHandler={() => retryHandler({ id })}
                analyticsProperties={{
                  screen,
                  error: `${error.message}`,
                  widgetId,
                  actionBy: widgetId,
                  title,
                }}
              />
            ) : (
              <Box
                display="flex"
                borderRadius="medium"
                overflow="hidden"
                backgroundColor="surface.background.gray.intense"
                ref={steppedSplitPaneRef}
              >
                <Box
                  minWidth="220px"
                  width="20%"
                  flexShrink="0"
                  display="flex"
                  flexDirection="column"
                >
                  <StepSelector
                    steps={components}
                    handleStepSelectorClick={handleStepSelectorClick}
                    isLoading={isLoading || isRetrying}
                  />
                </Box>
                <Box padding="spacing.5" width="100%" display="flex" key={selectedStepId}>
                  {getPaneContent()}
                </Box>
              </Box>
            )}
          </CardBody>
        </Card>
      </Box>
    </SteppedSplitPaneContext.Provider>
  );
}

export default SteppedSplitPane;
