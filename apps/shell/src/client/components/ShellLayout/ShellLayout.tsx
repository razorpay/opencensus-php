import React from 'react';
import { Box } from '@razorpay/blade/components';
import { ConnectedNavigationContainer } from '../Navigation';
import { ShellNotificationManager } from '../ShellNotificationsManager';
import { ShellBackground } from './styled';
import { ThemeSwitcher } from './ThemeSwitcher';

interface ShellLayoutProps {
  children: React.ReactNode;
}

export const ShellLayout = ({ children }: ShellLayoutProps) => (
  <>
    <ThemeSwitcher />
    <ConnectedNavigationContainer>{children}</ConnectedNavigationContainer>
    <ShellNotificationManager />
  </>
);
