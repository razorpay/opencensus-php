import React from 'react';
import { Box } from '@razorpay/blade/components';
import Navigation from '../Navigation';
import { ShellNotificationManager } from '../ShellNotificationsManager';

export const ShellLayout = ({ children }) => (
  <Box minHeight="100vh">
    <Navigation>{children}</Navigation>
    <ShellNotificationManager />
  </Box>
);
