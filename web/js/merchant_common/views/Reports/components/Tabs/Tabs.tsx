/* eslint-disable */
import React from 'react';
import { NavLink, Outlet, Route, Routes, useLocation } from 'react-router-dom';
import { StyledTab, TabsHeader } from './styled';
import { TabsPropType } from './types';
import { Box, Text } from 'merchant_common/views/Reports/components';

const Tab = ({ children, to, exact }: any) => {
  const { pathname } = useLocation();
  const isActive = pathname === to;
  return (
    <NavLink aria-label={children} to={to} end={exact ?? false} key={to}>
      <Text variant="body" weight="bold" size="medium">
        <StyledTab as="span" active={isActive}>
          {children}
        </StyledTab>
      </Text>
    </NavLink>
  );
};

const TabPanel = ({ children }) => {
  return <Box>{children}</Box>;
};

export const Tabs = ({ tabs, basePath }: TabsPropType): JSX.Element => {
  const attachBasePath = (to: string) => {
    return `${basePath}${to}`;
  };
  return (
    <Box padding={['spacing.7', 'spacing.6', 'spacing.7', 'spacing.6']}>
      <TabsHeader>
        {tabs.map(({ to, exact, label }) => {
          return (
            <Tab to={attachBasePath(to)} exact={exact} key={to}>
              {label}
            </Tab>
          );
        })}
      </TabsHeader>
      <Routes>
        <Route
          element={
            <Box>
              <TabPanel>
                <Outlet />
              </TabPanel>
            </Box>
          }
        >
          {tabs.map(({ to, component: Component }) => {
            const isIndex = to === '/reports';
            return (
              <Route
                element={<Component />}
                index={isIndex}
                path={isIndex ? '' : `${to.replace('/reports/', '')}/*`}
              />
            );
          })}
        </Route>
      </Routes>
    </Box>
  );
};
