import React from 'react';
import {
  Box,
  Text,
  Button,
  Heading,
  Badge,
  ChevronRightIcon,
  Link,
  CloseIcon,
} from '@razorpay/blade/components';
import { CardContainer, CardInner, CardFace, Image, ProductNameWrapper } from './styled';
import { BentoCardProps } from './types';
import { useNavigate } from 'react-router-dom';
import {
  trackProductVersioningWidgetClicked,
  trackProductVersioningWidgetFlipAction,
} from './track';

const BentoCard: React.FC<BentoCardProps> = ({
  id,
  title,
  productName,
  tags,
  image,
  flipped,
  onFlip,
  onClose,
  isMobile,
  boxType,
  backText,
  ctaText,
  ctaLink,
  imageBgColor,
  cardSetType,
}) => {
  const navigate = useNavigate();

  // Desktop: flip back on mouse leave
  const handleMouseLeave = () => {
    if (!isMobile && flipped) onClose();
  };

  const handleCtaClick = () => {
    trackProductVersioningWidgetClicked({
      productClicked: id,
      clickButtonName: ctaText,
      cardSetType,
      // screen,
    });
    if (ctaLink) {
      if (/^https?:\/\//i.test(ctaLink)) {
        window.open(ctaLink, '_blank', 'noopener,noreferrer');
      } else {
        navigate(ctaLink);
      }
    }
  };

  const handleFlipButtonClick = () => {
    trackProductVersioningWidgetFlipAction({
      productClicked: id,
      actionName: 'Flip',
      cardSetType,
      // screen
    });
    onFlip();
  };

  const handleCloseButtonClick = () => {
    trackProductVersioningWidgetFlipAction({
      productClicked: id,
      actionName: 'Close',
      cardSetType,
      // screen
    });
    onClose();
  };

  const _tags = boxType === 'small' ? [tags[0]] : tags;

  return (
    <CardContainer
      tabIndex={0}
      aria-pressed={!!flipped}
      onMouseLeave={handleMouseLeave}
      data-testid={`bento-card-${id}`}
    >
      <CardInner flipped={flipped}>
        {/* Front Face */}
        <CardFace>
          <Box
            display="flex"
            flexDirection="column"
            width="100%"
            justifyContent="space-between"
            flex={1}
          >
            <Box padding="spacing.5">
              <Box display="flex" justifyContent="space-between" alignItems="center">
                <ProductNameWrapper>
                  <Heading
                    size="small"
                    weight="semibold"
                    color="surface.text.primary.normal"
                    textAlign="left"
                  >
                    {productName}
                  </Heading>
                </ProductNameWrapper>
                <Button
                  icon={ChevronRightIcon}
                  variant="tertiary"
                  size="xsmall"
                  onClick={handleFlipButtonClick}
                  testID={`flip-${id}`}
                />
              </Box>
              <Heading
                size="medium"
                weight="semibold"
                color="surface.text.gray.normal"
                marginY="spacing.4"
                textAlign="left"
              >
                {title}
              </Heading>
              <Box display="flex" gap="spacing.3" marginBottom="spacing.5">
                {_tags.map((tag) => (
                  <Badge
                    color="positive"
                    size="medium"
                    emphasis="subtle"
                    key={`${tag}-${boxType}-${_tags.length}`}
                  >
                    {tag}
                  </Badge>
                ))}
              </Box>
            </Box>
            <Box>
              <Image src={image} alt="Payment Interface" backgroundColor={imageBgColor} />
            </Box>
          </Box>
        </CardFace>
        {/* Back Face */}
        <CardFace back>
          <Box
            display="flex"
            flexDirection="column"
            justifyContent="space-between"
            height="100%"
            width="100%"
            padding="spacing.6"
            borderRadius="large"
          >
            <Box flex={1}>
              <Box
                display="flex"
                justifyContent="space-between"
                marginBottom="spacing.5"
                alignItems="center"
                gap="spacing.5"
              >
                <Heading
                  size="medium"
                  weight="semibold"
                  color="surface.text.gray.normal"
                  textAlign="left"
                >
                  {title}
                </Heading>
                {isMobile && (
                  <Box alignSelf="flex-start">
                    <Button
                      icon={CloseIcon}
                      variant="tertiary"
                      size="xsmall"
                      onClick={handleCloseButtonClick}
                      testID={`close-${id}`}
                    />
                  </Box>
                )}
              </Box>

              <Text size="large" weight="regular" color="surface.text.gray.subtle" textAlign="left">
                {backText}
              </Text>
            </Box>
            {ctaText && ctaLink && (
              <Link
                icon={ChevronRightIcon}
                iconPosition="right"
                size="large"
                onClick={handleCtaClick}
              >
                {ctaText}
              </Link>
            )}
          </Box>
        </CardFace>
      </CardInner>
    </CardContainer>
  );
};

export default React.memo(BentoCard);
