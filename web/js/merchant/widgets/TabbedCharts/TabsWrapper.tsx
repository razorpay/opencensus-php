import React, { useState } from 'react';
import { Box } from '@razorpay/blade/components';
import styled from 'styled-components';

import TabCard from './TabCard';
import { TabsWrapperProps } from './types';
import { track } from 'merchant/widgets/utils';

export const TabCardWrapper = styled.div`
  display: flex;
  flex: 1;
  cursor: pointer;
`;

const TabsWrapper: React.FC<TabsWrapperProps> = ({ children, analyticsProperties }) => {
  const [activeTab, setActiveTab] = useState<string>(children[0]?.props?.id);
  const handleTabClick = (tabId: string, tabData) => {
    const { title, id, type } = tabData;

    const { screen, ...rest } = analyticsProperties;
    setActiveTab(tabId);
    const subWidgetId = `${analyticsProperties.widgetId}.${type}.${id}`;
    track({
      screen,
      objectName: `tab`,
      actionName: 'clicked',
      properties: {
        ...rest,
        subWidgetId,
        actionBy: subWidgetId,
        title,
      },
    });
  };

  if (children.length === 0) {
    return null;
  }

  return (
    <Box display="flex" flexDirection="column">
      <Box display="flex" flexDirection="row" overflowX="scroll">
        {children.map((child, index) => {
          const { id, tabData } = child.props;
          return (
            <TabCardWrapper
              key={`tab-${id}`}
              onClick={() => handleTabClick(id, tabData)}
              data-testid={`tab-${id}`}
            >
              <TabCard isActive={activeTab === id} tabData={tabData} cardPosition={index} />
            </TabCardWrapper>
          );
        })}
      </Box>
      <Box marginTop="spacing.5">
        {children.map((child) => (child.props.id === activeTab ? child : null))}
      </Box>
    </Box>
  );
};

export default TabsWrapper;
