import React, { useEffect } from 'react';
import { Box, Carousel, CarouselItem, Heading } from '@razorpay/blade/components';
import { CommonWidgetProps } from 'merchant/widgets/types';
import { CarouselWidgetProps } from 'merchant/widgets/Carousel/types';
import { CarouselWidgetWrapper } from './styled';

import { useRetryWidget } from 'merchant/widgets/hooks';
import { CarouselWidgetLoader } from 'merchant/widgets/Carousel/Loader';
import { ErrorState } from 'merchant/widgets/common/ErrorState';
import { getSubWidget } from 'merchant/widgets/Carousel/utils';
import { getUcsAliasFromQueryKey, track } from 'merchant/widgets/utils';
import { getWidgetStyles } from '../common/utils';

export const CarouselWidget: React.FC<CarouselWidgetProps & CommonWidgetProps> = ({
  title,
  components,
  background_img,
  styles,
  isLoading,
  error,
  id,
  queryKey,
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
  }, [error, isLoading, isRetrying]);

  if (isLoading || isRetrying)
    return <CarouselWidgetLoader title={title} components={components} styles={styles} />;

  if (components.length === 0) return null;

  return (
    <CarouselWidgetWrapper width={styles?.width} backgroundImage={background_img} hide={!title}>
      {title ? (
        <Box display="flex" gap="spacing.2" marginBottom="spacing.6">
          <Heading
            color={background_img ? 'surface.text.staticWhite.normal' : undefined}
            size="medium"
          >
            {title}
          </Heading>
        </Box>
      ) : null}
      {error ? (
        <ErrorState
          backgroundColor="surface.background.gray.intense"
          text={`${title} couldn't be loaded`}
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
        <Carousel
          carouselItemWidth={styles?.inherited_styles?.carousel_product_card?.width ?? '284px'}
          visibleItems="autofit"
          navigationButtonPosition="side"
        >
          {components.map((componentData) => (
            <CarouselItem key={componentData.id}>
              {getSubWidget({
                widget: {
                  ...componentData,
                  styles: getWidgetStyles(
                    styles?.inherited_styles?.[componentData.type],
                    componentData.styles,
                  ),
                },
                isLoading,
                queryKey,
                analyticsProperties: { widgetId, screen },
              })}
            </CarouselItem>
          ))}
        </Carousel>
      )}
    </CarouselWidgetWrapper>
  );
};
