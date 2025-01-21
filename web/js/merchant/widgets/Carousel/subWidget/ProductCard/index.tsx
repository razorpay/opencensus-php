import React, { Fragment } from 'react';
import { ArrowUpRightIcon, Box, Text } from '@razorpay/blade/components';
import { ProductCardWidgetProps } from 'merchant/widgets/Carousel/subWidget/ProductCard/types';
import { AnimatedBox } from 'merchant/widgets/Carousel/subWidget/ProductCard/styled';
import { CommonWidgetProps } from 'merchant/widgets/types';
import { ProductCardWidgetLoader } from 'merchant/widgets/Carousel/subWidget/ProductCard/Loader';
import { getCommonWidget, makeLink } from 'merchant/widgets/common/utils';
import { useMobile } from 'common/hooks/useMobile';
import { getBackgroundImage, track } from 'merchant/widgets/utils';
import { useNavigate } from 'react-router-dom';

export const ProductCardWidget: React.FC<
  ProductCardWidgetProps & Pick<CommonWidgetProps, 'isLoading' | 'analyticsProperties'>
> = ({
  id,
  type,
  title,
  description,
  actions,
  background_img,
  isLoading,
  analyticsProperties = {},
}) => {
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
    <div onClick={handleProductCardWidgetClick}>
      <Box
        onMouseEnter={!isMobile ? () => setIsCardHovered(true) : undefined}
        onMouseLeave={!isMobile ? () => setIsCardHovered(false) : undefined}
        testID="product-card-widget"
        width="284px"
        height="416px"
        display="flex"
        flexDirection="column"
        borderRadius="medium"
        overflow="hidden"
      >
        <Box
          backgroundImage={getBackgroundImage(background_img)}
          backgroundSize="cover"
          backgroundPosition="center center"
          height="100%"
        />
        <Box
          width="100%"
          padding="spacing.6"
          backgroundColor="surface.background.gray.intense"
          display="flex"
          flexDirection="column"
          gap="spacing.3"
        >
          <Box display="flex" alignItems="center" justifyContent="space-between">
            <Text size="large" weight="semibold">
              {title}
            </Text>
            {isMobile ? <ArrowUpRightIcon color="surface.icon.gray.muted" /> : null}
          </Box>
          <Text
            marginBottom="spacing.3"
            color="surface.text.gray.muted"
            truncateAfterLines={!isCardHovered ? 2 : undefined}
          >
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
    </div>
  );
};
