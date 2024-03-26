import React from 'react';
import { Box, Text } from '@razorpay/blade/components';

import { carouselDataWidgetIconMap } from 'merchant/widgets/CarouselWithCount/subWidget/CarouselData/utils';
import { CarouselDataWidgetProps } from 'merchant/widgets/CarouselWithCount/subWidget/CarouselData/types';
import { CarouselDataWidgetLoader } from 'merchant/widgets/CarouselWithCount/subWidget/CarouselData/Loader';
import { makeLink, getCommonWidget } from 'merchant/widgets/common/utils';
import { CarouselDataWidgetWrapper } from './styled';
import { useMobile } from 'common/hooks/useMobile';
import { useNavigate } from 'react-router-dom';
import { track } from 'merchant/widgets/utils';

// Widget's error and loading states are handled by the parent component (CarouselWithCountWidget)
export const CarouselDataWidget: React.FC<CarouselDataWidgetProps> = ({
  title,
  description,
  variant,
  action,
  isLoading,
  analyticsProperties = {},
  id,
  type,
}): JSX.Element => {
  const isMobile = useMobile();
  const navigate = useNavigate();

  const navigationKey = action?.action ?? '';
  const navigationParams = action?.action_params ?? {};

  const variantIcon = carouselDataWidgetIconMap[variant];

  const { screen, widgetId } = analyticsProperties;
  const subWidgetId = `${widgetId}.${type}.${id}`;

  const dataWidgetNavigateHandler = () => {
    if (/^http/i.test(navigationKey)) {
      window.open(navigationKey, '_blank');
    } else {
      const navigationURL = makeLink(navigationKey, navigationParams);
      if (navigationURL) navigate(navigationURL);
    }
    track({
      objectName: 'link',
      actionName: 'clicked',
      screen: analyticsProperties?.screen,
      properties: {
        ...analyticsProperties,
        subWidgetId,
        actionBy: subWidgetId,
        title,
        variant,
        action: navigationKey,
        actionLabel: action?.title,
      },
    });
  };

  if (isLoading) return <CarouselDataWidgetLoader />;

  return (
    <CarouselDataWidgetWrapper
      isMobile={isMobile}
      onClick={dataWidgetNavigateHandler}
      hasAction={Boolean(navigationKey)}
    >
      {variantIcon ? <Box testID="carousel-data-icon">{variantIcon()}</Box> : null}
      <Box gap="spacing.2" display="flex" flexDirection="column" paddingRight="spacing.2">
        <Text weight="bold">{title}</Text>
        <Text type="subdued">{description}</Text>
      </Box>
      {action ? (
        <Box alignItems="center" display="flex">
          {getCommonWidget({
            widget: action,
            analyticsProperties: {
              ...analyticsProperties,
              subWidgetId,
              actionBy: subWidgetId,
              title,
              screen,
              variant,
            },
          })}
        </Box>
      ) : null}
    </CarouselDataWidgetWrapper>
  );
};
