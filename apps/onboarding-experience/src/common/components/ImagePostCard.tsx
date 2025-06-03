import React from 'react';
import { Box, Text, Link, Card, Badge, IconComponent } from '@razorpay/blade/components';
import { isMobileDevice } from '@libs/shared-utils';

export interface ImagePostCardProps {
  tagIcon: IconComponent;
  tagText: string;
  image: string;
  imageFit?: string;
  imageHeight?: string;
  title: string;
  description: string;
  linkIcon: IconComponent;
  linkText: string;
  handleClick?: () => void;
  linkUrl?: string;
  maxContentHeight?: 'small' | 'large';
}

const ImagePostCard = ({
  image,
  imageFit = 'cover',
  imageHeight = '100%',
  tagIcon,
  tagText,
  title,
  description,
  linkUrl,
  linkIcon,
  linkText,
  handleClick,
  maxContentHeight = 'large',
}: ImagePostCardProps): JSX.Element => {
  const isMobile = isMobileDevice();
  return (
    <Card width="100%" elevation="none" padding={isMobile ? 'spacing.5' : 'spacing.7'}>
      <Box
        display="flex"
        flexDirection="column"
        alignItems="flex-start"
        gap={isMobile ? 'spacing.5' : 'spacing.7'}
        flex="1 0 0"
        alignSelf="stretch"
      >
        <img
          src={image}
          alt={`${title}`}
          style={{ width: '100%', height: imageHeight, objectFit: imageFit }}
        />
        <Box
          display="flex"
          flexDirection="column"
          alignItems="flex-start"
          gap="spacing.5"
          alignSelf="stretch"
        >
          <Badge icon={tagIcon} size="medium">
            {tagText}
          </Badge>
          <Box
            display="flex"
            flexDirection="column"
            alignItems="flex-start"
            gap="spacing.3"
            alignSelf="stretch"
            minHeight={{ base: 'fit-content', m: maxContentHeight === 'small' ? '85px' : '115px' }}
          >
            <Text
              weight="semibold"
              alignSelf="strech"
              color="surface.text.gray.normal"
              size={isMobile ? 'medium' : 'large'}
            >
              {title}
            </Text>
            <Text
              size={isMobile ? 'small' : 'medium'}
              weight="regular"
              alignSelf="strech"
              color="surface.text.gray.normal"
            >
              {description}
            </Text>
          </Box>
          {linkUrl && (
            <Link
              icon={linkIcon}
              iconPosition="right"
              variant="anchor"
              href={linkUrl}
              target={'_blank'}
              size={isMobile ? 'small' : 'medium'}
            >
              {linkText}
            </Link>
          )}
          {handleClick && (
            <Link
              icon={linkIcon}
              iconPosition="right"
              onClick={handleClick}
              size={isMobile ? 'small' : 'medium'}
            >
              {linkText}
            </Link>
          )}
        </Box>
      </Box>
    </Card>
  );
};

export default ImagePostCard;
