import React, { useEffect } from 'react';
import { Box, Carousel, CarouselItem, Heading } from '@razorpay/blade/components';

import { CarouselWithCountWidgetProps } from 'merchant/widgets/CarouselWithCount/types';
import { CarouselWithCountWidgetLoader } from 'merchant/widgets/CarouselWithCount/Loader';
import { CarouselWithCountWrapper } from 'merchant/widgets/CarouselWithCount/styled';
import { ErrorState } from 'merchant/widgets/common/ErrorState';
import { useRetryWidget } from 'merchant/widgets/hooks';
import { CommonWidgetProps } from 'merchant/widgets/types';
import { getSubWidget } from 'merchant/widgets/CarouselWithCount/utils';
import { getUcsAliasFromQueryKey, track } from 'merchant/widgets/utils';

export const CarouselWithCountWidget: React.FC<
  CarouselWithCountWidgetProps & CommonWidgetProps
> = ({
  title,
  components,
  isLoading,
  error,
  id,
  queryKey,
  background_img,
  type,
}): JSX.Element | null => {
  const [isRetrying, retryHandler] = useRetryWidget(queryKey);

  const screen = getUcsAliasFromQueryKey(queryKey) ?? '';
  const widgetId = `merchantDashboard.${screen}.${type}.${id}`;

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

  if (isLoading || isRetrying)
    return (
      <CarouselWithCountWidgetLoader
        title={title}
        background_img={background_img}
        components={components}
      />
    );

  if (components.length === 0)
    return (
      <CarouselWithCountWrapper background_img={background_img}>
        <Heading
          color={background_img ? 'feedback.text.information.highContrast' : undefined}
          size="large"
        >
          {title} - You're all caught up!
        </Heading>
      </CarouselWithCountWrapper>
    );

  return (
    <CarouselWithCountWrapper background_img={background_img}>
      <Box display="flex" gap="spacing.2" marginBottom="spacing.6" alignItems="center">
        <Heading
          color={background_img ? 'feedback.text.information.highContrast' : undefined}
          size="large"
        >
          {title}
        </Heading>
        {error ? null : (
          <Heading size="medium" type="subtle">
            ({components.length})
          </Heading>
        )}
      </Box>
      {error ? (
        <ErrorState
          backgroundColor="surface.background.level2.lowContrast"
          text={`${title} couldn't be loaded`}
          analyticsProperties={{
            screen,
            error: `${error.message}`,
            widgetId,
            actionBy: widgetId,
            title,
          }}
          retryHandler={() => retryHandler({ id })}
        />
      ) : (
        <Carousel carouselItemWidth="375px" navigationButtonPosition="side" showIndicators={false}>
          {components.map((componentData) => (
            <CarouselItem key={componentData.id}>
              {getSubWidget({
                widget: componentData,
                isLoading,
                queryKey,
                analyticsProperties: {
                  widgetId,
                  screen,
                },
              })}
            </CarouselItem>
          ))}
        </Carousel>
      )}
    </CarouselWithCountWrapper>
  );
};
