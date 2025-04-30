import React from 'react';
import { Box, Text, Link, Card, Badge, IconComponent } from '@razorpay/blade/components';
import { isMobileDevice } from '@libs/shared-utils';

interface ImagePostCardProps {
  tagIcon: IconComponent;
  tagText: string;
  image: string;
  title: string;
  description: string;
  linkIcon: IconComponent;
  linkText: string;
  handleClick: () => void;
}

const ImagePostCard = ({
  image,
  tagIcon,
  tagText,
  title,
  description,
  linkIcon,
  linkText,
  handleClick,
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
        <img width="100%" src={image} alt={`${title}`} />
        <Box
          display="flex"
          flexDirection="column"
          alignItems="flex-start"
          gap="spacing.5"
          alignSelf="stretch"
        >
          <Badge icon={tagIcon}>{tagText}</Badge>
          <Box
            display="flex"
            flexDirection="column"
            alignItems="flex-start"
            gap="spacing.3"
            alignSelf="stretch"
            minHeight="85px"
          >
            <Text
              weight="semibold"
              alignSelf="strech"
              color="surface.text.gray.normal"
              size="medium"
            >
              {title}
            </Text>
            <Text weight="regular" alignSelf="strech" color="surface.text.gray.normal" size="small">
              {description}
            </Text>
          </Box>
          <Link onClick={handleClick} icon={linkIcon} iconPosition="right">
            {linkText}
          </Link>
        </Box>
      </Box>
    </Card>
  );
};

export default ImagePostCard;
