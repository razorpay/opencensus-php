import React, { useState } from 'react';
import { Story, Meta } from '@storybook/react/types-6-0.d';
import { Tabs, Tab, TabContent } from './index';

export default {
  title: 'Desktop Tabs',
  component: Tabs,
} as Meta;

const contentMap = {
  contact_details: <div>contact details tab</div>,
  business_overview: <div>business overview tab</div>,
  business_details: <div>business details tab</div>,
  bank_details: <div>bank details tabs</div>,
  documents: <div>documents tabs</div>,
};

const Template: Story = (args): React.ReactElement => {
  const [activeTabId, setActiveTabId] = useState<string>('contact_details');

  const getTabs = (): Array<JSX.Element> => {
    const tabs = [
      <Tab key="contact_details" title="Contact Details" tabId="contact_details" />,
      <Tab key="business_overview" title="Business Overview" tabId="business_overview" />,
      <Tab key="business_details" title="Business Details" tabId="business_details" />,
    ];
    if (args.isL1Submitted) {
      tabs.push(
        <Tab key="bank_details" title="Bank and Business Identification" tabId="bank_details" />,
        <Tab key="documents" title="Documents Upload" tabId="documents" />,
      );
    }
    return tabs;
  };

  return (
    <div style={{ paddingTop: '10px', display: 'flex' }}>
      <Tabs
        activeTabId={activeTabId}
        onChange={(tabId) => {
          if (typeof tabId === 'string') {
            setActiveTabId(tabId);
          }
        }}
      >
        {getTabs()}
      </Tabs>
      <TabContent calculatedWidth="calc(100% - 310px)" isVisible={contentMap[activeTabId]}>
        {contentMap[activeTabId]}
      </TabContent>
    </div>
  );
};

export const DefaultDesktopTabs = Template.bind({});

DefaultDesktopTabs.args = { isL1Submitted: false };

const CustomTemplate: Story = (args): React.ReactElement => {
  const [activeTabId, setActiveTabId] = useState<string>('contact_details');

  return (
    <div style={{ paddingTop: '10px', display: 'flex' }}>
      <Tabs
        activeTabId={activeTabId}
        onChange={(tabId) => {
          if (typeof tabId === 'string') {
            setActiveTabId(tabId);
          }
        }}
      >
        <Tab
          key="contact_details"
          title="Contact Details"
          tabId="contact_details"
          completed={args.completed}
          hasError={args.hasError}
        />
        <Tab
          key="business_overview"
          title="Business Overview"
          tabId="business_overview"
          completed={args.completed}
          hasError={args.hasError}
        />
        <Tab
          key="business_details"
          title="Business Details"
          tabId="business_details"
          completed={args.completed}
          hasError={args.hasError}
        />
      </Tabs>
      <TabContent calculatedWidth="calc(100% - 310px)" isVisible={contentMap[activeTabId]}>
        {contentMap[activeTabId]}
      </TabContent>
    </div>
  );
};

export const TabsCompleted = CustomTemplate.bind({});

TabsCompleted.args = { completed: true, hasError: false };
