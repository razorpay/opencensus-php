import React from 'react';
import { Box, IconComponent, Link, Text } from '@razorpay/blade/components';

interface VerifiedPolicyPageCardProps {
  pageKey: string;
  Icon: IconComponent;
  title: string;
  url: string;
}

const VerifiedPolicyPageCard: React.FC<VerifiedPolicyPageCardProps> = ({
  pageKey,
  Icon,
  title,
  url,
}) => {
  return (
    <Box
      display="flex"
      flexDirection="row"
      padding="spacing.5"
      borderColor="surface.border.gray.muted"
      alignItems="center"
      gap="spacing.4"
      borderWidth="thick"
      borderRadius="medium"
      key={pageKey}
      testID={`verified-policy-page-card-${pageKey}`}
    >
      <Box alignSelf="flex-start" paddingTop="spacing.2">
        <Icon color="surface.icon.gray.normal" />
      </Box>
      <Box display="flex" flexDirection="column" gap="spacing.2">
        <Text color="surface.text.gray.normal" weight="medium">
          {title}
        </Text>
        <Link>{url}</Link>
      </Box>
    </Box>
  );
};

export default VerifiedPolicyPageCard;
