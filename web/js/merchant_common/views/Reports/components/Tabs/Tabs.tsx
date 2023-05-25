import React from 'react';
import { NavLink, withRouter } from 'react-router-dom';
import { ShowWhenRoute } from 'merchant/components/ShowWhen';
import { StyledTab, TabsHeader } from './styled';
import { TabsPropType } from './types';
import { Text } from 'merchant_common/views/Reports/components';

const Tab = withRouter(({ children, to, exact, location }: any) => {
  const { pathname } = location;
  const isActive = pathname === to;
  return (
    <NavLink aria-label={children} to={to} exact={exact} key={to}>
      <Text variant="body" weight="bold" size="medium">
        <StyledTab as="span" active={isActive}>
          {children}
        </StyledTab>
      </Text>
    </NavLink>
  );
});

const TabPanel = ({ children }) => {
  return <div>{children}</div>;
};

export const Tabs = ({ tabs, basePath }: TabsPropType): JSX.Element => {
  const attachBasePath = (to: string) => {
    return `${basePath}${to}`;
  };

  return (
    <div
      style={{
        padding: '24px 20px',
      }}
    >
      <TabsHeader>
        {tabs.map(({ to, exact, label }) => {
          return (
            <Tab to={attachBasePath(to)} exact={exact} key={to}>
              {label}
            </Tab>
          );
        })}
      </TabsHeader>

      {tabs.map(({ to, component }) => {
        return (
          <div key={to}>
            <TabPanel>
              <ShowWhenRoute key={to} exact path={attachBasePath(to)} component={component} />
            </TabPanel>
          </div>
        );
      })}
    </div>
  );
};
