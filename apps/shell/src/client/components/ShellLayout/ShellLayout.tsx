import React from 'react';
import { ConnectedNavigationContainer } from '../Navigation';
import { ShellNotificationManager } from '../ShellNotificationsManager';
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
