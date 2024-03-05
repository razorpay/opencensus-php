import React, { useState, useEffect } from 'react';
import styled from 'styled-components';
import View from '@razorpay/blade-old/src/atoms/View';
import Flex from '@razorpay/blade-old/src/atoms/Flex';
import Size from '@razorpay/blade-old/src/atoms/Size';
import Space from '@razorpay/blade-old/src/atoms/Space';
import TabContent from './TabContent';

const TabBar = styled(View)`
  overflow-x: scroll;
  box-sizing: border-box;
  box-shadow: 0px 4px 15px rgba(11, 112, 231, 0.05);
  background-color: ${({ theme }) => theme.bladeOld.colors.background[200]};
`;

const TabContentContainer = styled(View)`
  background-color: ${({ theme }) => theme.bladeOld.colors.background[400]};
`;
const StyledSeparator = styled(View)`
  height: 1px;
  width: 100%;
  background-color: ${({ theme }) => theme.bladeOld.colors.shade[930]};
`;
interface TabsChildProps {
  isActive: boolean;
  tabId: string;
  children: React.ReactElement | React.ReactElement[];
  onClick: () => void;
}

export interface TabsProps {
  children: React.ReactElement<TabsChildProps> | React.ReactElement[];
  activeTabId: string | number;
  onChange?: (tabId: string | number) => void;
}

const Tabs = ({
  onChange,
  children,
  activeTabId: externalActiveTabId,
}: TabsProps): React.ReactElement => {
  const [activeTabId, setActiveTabId] = useState<string | number>(externalActiveTabId);
  useEffect(() => {
    setActiveTabId(externalActiveTabId);
  }, [externalActiveTabId]);
  const tabs = React.Children.map(children, (child: React.ReactElement<TabsChildProps>) => {
    if (!child) {
      return undefined;
    }

    return React.cloneElement(child, {
      isActive: activeTabId === child.props.tabId,
      onClick: () => {
        setActiveTabId(child.props.tabId);
        if (typeof onChange === 'function') {
          onChange(child.props.tabId);
        }
      },
    });
  });

  const tabsContent = React.Children.map(children, (child: React.ReactElement<TabsChildProps>) => {
    if (!child) {
      return undefined;
    }

    const isVisible = child.props.tabId === activeTabId;
    return <TabContent isVisible={isVisible}>{child.props.children}</TabContent>;
  });

  return (
    <View>
      <Flex>
        <Size height={4.5}>
          <TabBar>{tabs}</TabBar>
        </Size>
      </Flex>
      <StyledSeparator />
      <Space padding={[2]}>
        <TabContentContainer>{tabsContent}</TabContentContainer>
      </Space>
    </View>
  );
};

export default Tabs;
