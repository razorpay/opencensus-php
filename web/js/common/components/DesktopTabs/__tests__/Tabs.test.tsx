import React, { useState } from 'react';
import '@testing-library/jest-dom/extend-expect';
import { Tabs, Tab, TabContent } from 'common/components/DesktopTabs/index';
import { render, fireEvent } from 'test-utils';

const contentMap = {
  contact_details: <div>contact details tab content</div>,
  business_overview: <div>business overview tab content</div>,
  business_details: <div>business details tab content</div>,
  bank_details: <div>bank details tab content</div>,
  documents: null,
};

const App = () => {
  const [activeTabId, setActiveTabId] = useState<string>('contact_details');
  const getTabs = (): Array<JSX.Element> => {
    const tabs = [
      <Tab key="contact_details" title="Contact Details" tabId="contact_details" />,
      <Tab key="business_overview" title="Business Overview" tabId="business_overview" />,
      <Tab key="business_details" title="Business Details" tabId="business_details" />,
      <Tab key="bank_details" title="Bank and Business Identification" tabId="bank_details" />,
      <Tab key="documents" title="Documents Upload" tabId="documents" />,
    ];
    return tabs;
  };
  return (
    <div style={{ display: 'flex' }}>
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

describe('Desktop Tabs', () => {
  test('should render all five tab and default contact details tab content', () => {
    const { getByText } = render(<App />, {});
    expect(getByText('contact details tab content')).toBeInTheDocument();
    expect(getByText('Contact Details')).toBeInTheDocument();
    expect(getByText('Business Overview')).toBeInTheDocument();
    expect(getByText('Business Details')).toBeInTheDocument();
    expect(getByText('Bank and Business Identification')).toBeInTheDocument();
    expect(getByText('Documents Upload')).toBeInTheDocument();
    expect(<App />).toMatchSnapshot();
  });

  test('should change the content of tabs on click', () => {
    const { getByText } = render(<App />, {});
    expect(getByText('contact details tab content')).toBeInTheDocument();
    fireEvent.click(getByText('Business Overview'));
    expect(getByText('business overview tab content')).toBeInTheDocument();
    fireEvent.click(getByText('Business Details'));
    expect(getByText('business details tab content')).toBeInTheDocument();
    fireEvent.click(getByText('Bank and Business Identification'));
    expect(getByText('bank details tab content')).toBeInTheDocument();
    fireEvent.click(getByText('Documents Upload'));
    expect(<App />).toMatchSnapshot();
  });
});
