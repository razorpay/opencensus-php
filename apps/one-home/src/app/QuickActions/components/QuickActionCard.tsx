import React from 'react';
import { Card, CardBody, Box, Text, useTheme, IconComponent } from '@razorpay/blade/components';

interface QuickActionCardProps {
  icon: IconComponent;
  title: string;
  onClick: () => void;
  isMobile: boolean;
}

const QuickActionCard: React.FC<QuickActionCardProps> = ({
  icon: IconComponent,
  title,
  onClick,
  isMobile,
}) => {
  return (
    <Card
      padding="spacing.5"
      minWidth={{ base: '120px', m: '132px', l: '132px', xl: '132px' }}
      elevation="none"
      borderRadius="medium"
      onClick={onClick}
      backgroundColor="surface.background.gray.subtle"
      accessibilityLabel={title}
    >
      <CardBody>
        <Box gap="spacing.3" flexWrap="nowrap" flexShrink={0}>
          <IconComponent color="surface.icon.primary.normal" size="large" />
          <Text
            variant="body"
            weight="semibold"
            color="surface.text.primary.normal"
            size={isMobile ? 'small' : 'medium'}
            truncateAfterLines={1}
          >
            {title}
          </Text>
        </Box>
      </CardBody>
    </Card>
  );
};

export default QuickActionCard;
