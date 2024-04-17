import React, { Fragment } from 'react';
import { ArrowUpRightIcon, Box, Text } from '@razorpay/blade/components';
import { ProductCardWidgetProps } from 'merchant/widgets/Carousel/subWidget/ProductCard/types';
import {
  AnimatedBox,
  ProductCardWidgetWrapper,
} from 'merchant/widgets/Carousel/subWidget/ProductCard/styled';
import { CommonWidgetProps } from 'merchant/widgets/types';
import { ProductCardWidgetLoader } from 'merchant/widgets/Carousel/subWidget/ProductCard/Loader';
import { getCommonWidget, makeLink } from 'merchant/widgets/common/utils';
import { useMobile } from 'common/hooks/useMobile';
import { getBackgroundImage, track } from 'merchant/widgets/utils';
import { useNavigate } from 'react-router-dom';

export const ProductCardWidget: React.FC<
  ProductCardWidgetProps & Pick<CommonWidgetProps, 'isLoading' | 'analyticsProperties'>
> = ({
  title,
  description,
  background_img: backgroundImage,
  actions,
  isLoading,
  analyticsProperties = {},
  id,
  type,
}): JSX.Element | null => {
  const [isCardHovered, setIsCardHovered] = React.useState(false);
  const isMobile = useMobile();
  const navigate = useNavigate();

  if (isLoading) return <ProductCardWidgetLoader />;

  const [firstAction] = actions;
  const navigationKey = firstAction?.action ?? '';

  const { screen, widgetId } = analyticsProperties;

  const subWidgetId = `${widgetId}.${type}.${id}`;

  const analyticsObject = {
    widgetId,
    subWidgetId,
    actionBy: subWidgetId,
    title,
  };

  const handleProductCardWidgetClick = () => {
    if (isMobile) {
      if (/^http/i.test(navigationKey)) {
        window.open(navigationKey, '_blank');
      } else {
        const navigationURL = makeLink(navigationKey);
        if (navigationURL) navigate(navigationURL);
      }
      track({
        objectName: 'link',
        actionName: 'clicked',
        screen: analyticsProperties?.screen,
        properties: {
          ...analyticsObject,
          action: firstAction.action,
          actionLabel: firstAction.title,
        },
      });
    }
  };

  return (
    <ProductCardWidgetWrapper
      onMouseEnter={() => !isMobile && setIsCardHovered(true)}
      onMouseLeave={() => !isMobile && setIsCardHovered(false)}
      onClick={handleProductCardWidgetClick}
      data-testid="product-card-widget"
    >
      <Box
        backgroundImage={getBackgroundImage(backgroundImage)}
        backgroundSize="cover"
        backgroundPosition="center center"
        borderTopLeftRadius="medium"
        borderTopRightRadius="medium"
        height="300px"
      />
      <Box
        position="relative"
        backgroundColor="surface.background.gray.intense"
        height="116px"
        borderBottomLeftRadius="medium"
        borderBottomRightRadius="medium"
      >
        <Box
          padding="spacing.6"
          backgroundColor="surface.background.gray.intense"
          display="flex"
          flexDirection="column"
          gap="spacing.3"
          position="absolute"
          bottom="spacing.0"
          borderBottomLeftRadius="medium"
          borderBottomRightRadius="medium"
        >
          <Box display="flex" alignItems="center" justifyContent="space-between">
            <Text size="large" weight="semibold">
              {title}
            </Text>
            {isMobile ? <ArrowUpRightIcon color="surface.icon.gray.muted" /> : null}
          </Box>
          <Text marginBottom="spacing.3" color="surface.text.gray.muted">
            {description}
          </Text>
          <Box>
            {isCardHovered && !isMobile ? (
              <AnimatedBox>
                {actions.map((action) => (
                  <Fragment key={action.title}>
                    {getCommonWidget({
                      widget: action,
                      analyticsProperties: { ...analyticsObject, screen },
                    })}
                  </Fragment>
                ))}
              </AnimatedBox>
            ) : null}
          </Box>
        </Box>
      </Box>
    </ProductCardWidgetWrapper>
  );
};
