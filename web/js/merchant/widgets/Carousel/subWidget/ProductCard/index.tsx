import React, { Fragment } from 'react';
import { ArrowUpRightIcon, Box, Text, Heading } from '@razorpay/blade/components';
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
  styles,
  isLoading,
  analyticsProperties = {},
}) => {
  const [isCardHovered, setIsCardHovered] = React.useState(false);
  const isMobile = useMobile();
  const navigate = useNavigate();

  if (isLoading) return <ProductCardWidgetLoader styles={styles} />;

  const [firstAction] = actions;
  const navigationKey = firstAction?.action ?? '';

  const isShownOnHover = styles?.show_on_hover ?? true;
  const isToggleResponsive = styles?.toggle_responsive ?? false;

  const { screen, widgetId } = analyticsProperties;
  const subWidgetId = `${widgetId}.${type}.${id}`;

  const analyticsObject = {
    widgetId,
    subWidgetId,
    actionBy: subWidgetId,
    title,
  };

  const handleProductCardWidgetClick = () => {
    if (isMobile && isShownOnHover) {
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
        onMouseEnter={() => !isMobile && isShownOnHover && setIsCardHovered(true)}
        onMouseLeave={() => !isMobile && isShownOnHover && setIsCardHovered(false)}
        testID="product-card-widget"
        width={styles?.width ?? '284px'}
        height={styles?.height ?? '416px'}
        position="relative"
        display={isToggleResponsive ? { base: 'block', s: 'flex', xl: 'block' } : undefined}
        borderRadius={styles?.border_radius ?? 'medium'}
        overflow="hidden"
      >
        <Box
          backgroundImage={getBackgroundImage(background_img)}
          backgroundSize="cover"
          backgroundPosition="center center"
          height={isToggleResponsive ? { base: '70%', s: 'auto', xl: '75%' } : '300px'}
          width={isToggleResponsive ? { base: '100%', s: '200px', xl: '100%' } : undefined}
        />
        <Box
          width="100%"
          padding="spacing.6"
          backgroundColor="surface.background.gray.intense"
          display="flex"
          flexDirection="column"
          gap="spacing.3"
          position={
            isToggleResponsive ? { base: 'absolute', s: 'static', xl: 'absolute' } : 'absolute'
          }
          bottom="spacing.0"
        >
          <Box display="flex" alignItems="center" justifyContent="space-between">
            <Heading size="medium">{title}</Heading>
            {isShownOnHover && isMobile ? (
              <ArrowUpRightIcon color="surface.icon.gray.muted" />
            ) : null}
          </Box>
          <Text marginBottom="spacing.3" color="surface.text.gray.muted">
            {description}
          </Text>
          <Box>
            {(isCardHovered && !isMobile) || !isShownOnHover ? (
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
