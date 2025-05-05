import React from 'react';
import { Box, Text, Link, PlayCircleIcon, UserPlusIcon } from '@razorpay/blade/components';

const InviteTeamMember = () => {
  return (
    <Box
      backgroundColor="surface.background.gray.subtle"
      padding="spacing.4"
      display="flex"
      flexDirection={{ base: 'column', l: 'row' }}
      gap="spacing.4"
      borderRadius="medium"
    >
      <Text size="small" color="surface.text.gray.subtle">
        This is a technical step and you may need some help to integrate keys.
      </Text>
      <Link
        size="small"
        icon={UserPlusIcon}
        variant="button"
        onClick={() => {}}
        marginLeft={{ base: 'none', l: 'auto' }}
      >
        Invite your developer as an admin
      </Link>
      <Link
        size="small"
        color="neutral"
        icon={PlayCircleIcon}
        href="https://www.youtube.com/watch?v=6mJnOWZDhDo"
        rel="noreferrer noopener"
        target="_blank"
      >
        Watch a video on how to set up
      </Link>
    </Box>
  );
};

export default InviteTeamMember;
