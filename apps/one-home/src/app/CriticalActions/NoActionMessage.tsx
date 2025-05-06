import { Box, Text } from '@razorpay/blade/components';
import React from 'react';
import { NoActionMessageProps } from './types';

const NoActionMessage = ({ user }: NoActionMessageProps) => {
  return (
    <Box
      padding="spacing.7"
      display={{ base: 'block', m: 'flex' }}
      alignItems="center"
      backgroundColor="surface.background.gray.intense"
      borderRadius="large"
      flexDirection={{ base: 'column', l: 'row' }}
      textAlign="start"
    >
      {/* <ConfettiIcon marginRight="spacing.2" /> */}
      <Text
        marginRight="spacing.3"
        size="large"
        color="surface.text.gray.normal"
        weight="semibold"
        alignSelf="start"
      >{`🎉 You're all caught up${user.name ? `, ${user.name}` : ''}.`}</Text>

      <Text
        marginLeft={{ base: 'spacing.6', l: 'spacing.0' }}
        size="large"
        color="surface.text.gray.muted"
        alignSelf="start"
      >{`There are no actions that need your attention on Razorpay today.`}</Text>
    </Box>
  );
};

export default NoActionMessage;
