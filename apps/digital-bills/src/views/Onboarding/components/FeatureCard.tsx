import React from 'react';
import { Box, Heading, Text } from '@razorpay/blade/components';

type FeatureCardProps = {
  title: string;
  description: string;
  image: string;
};

const FeatureCard = ({ title, description, image }: FeatureCardProps) => {
  return (
    <Box
      maxWidth={{ base: '100%', l: '275px' }}
      padding="spacing.5"
      borderRadius="small"
      borderWidth="thin"
      borderColor="surface.border.primary.muted"
      backgroundColor="surface.background.gray.intense"
    >
      <img src={image} alt={`${title} icon`} />
      <Heading size="small" weight="semibold" marginTop="spacing.5">
        {title}
      </Heading>
      <Text size="small" weight="regular" marginTop="spacing.3">
        {description}
      </Text>
    </Box>
  );
};

export default FeatureCard;
