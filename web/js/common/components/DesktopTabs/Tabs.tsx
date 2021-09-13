import React, { useState, useEffect } from 'react';
import styled from 'styled-components';
import View from '@razorpay/blade-old/src/atoms/View';
import Flex from '@razorpay/blade-old/src/atoms/Flex';
import Size from '@razorpay/blade-old/src/atoms/Size';

const TabBar = styled(View)`
  box-sizing: border-box;
`;

interface ITabsChildProps {
  isActive: boolean;
  tabId: string;
  onClick: () => void;
}

interface ITabsProps {
  children: React.ReactElement<ITabsChildProps> | React.ReactElement[];
  activeTabId: string | number;
  onChange?: (tabId: string | number) => void;
}

const Tabs = ({
  onChange,
  children,
  activeTabId: externalActiveTabId,
}: ITabsProps): React.ReactElement => {
  const [activeTabId, setActiveTabId] = useState<string | number>(externalActiveTabId);

  useEffect(() => {
    setActiveTabId(externalActiveTabId);
  }, [externalActiveTabId]);

  const tabs = React.Children.map(children, (child: React.ReactElement<ITabsChildProps>) => {
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

  return (
    <Flex flexDirection="column" flex="none">
      <Size width={38.75}>
        <TabBar>{tabs}</TabBar>
      </Size>
    </Flex>
  );
};

export default Tabs;
