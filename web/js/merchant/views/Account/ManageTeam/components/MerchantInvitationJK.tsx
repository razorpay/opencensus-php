import React from 'react';
import { Box, Text } from '@razorpay/blade/components';

const MerchantInvitationJKOrg = ({ items, actions }) => {
  return (
    <Box>
      <Box
        backgroundColor="surface.background.gray.subtle"
        padding={['spacing.5', 'spacing.4', 'spacing.5', 'spacing.4']}
      >
        <Text color="surface.text.gray.normal" weight="semibold" size="large">
          Pending Invitations ({items.length})
        </Text>
      </Box>
      <Box>
        {items.map((item, index) => {
          return (
            <Box
              padding={['spacing.4', 'spacing.5', 'spacing.4', 'spacing.5']}
              key={index}
              display="flex"
              alignItems="center"
              justifyContent="space-between"
              backgroundColor="surface.background.gray.intense"
            >
              <div>
                <Text size="medium" color="surface.text.gray.normal" weight="medium">
                  {item.email}
                </Text>
                <Box display="flex" alignItems="center">
                  <Text color="surface.text.gray.muted" weight="regular" size="small">
                    {item.role_name}
                  </Text>
                </Box>
              </div>
              {actions.value(item, true)}
            </Box>
          );
        })}
      </Box>
    </Box>
  );
};

export default MerchantInvitationJKOrg;
