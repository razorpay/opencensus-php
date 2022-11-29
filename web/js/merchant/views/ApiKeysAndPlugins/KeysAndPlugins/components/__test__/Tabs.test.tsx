import React from 'react';
import { render, userEvent } from 'test-utils';
import {
  TabSwitcher,
  Tab,
  TabSwitcherProps,
} from 'merchant/views/ApiKeysAndPlugins/KeysAndPlugins/components/Tabs';

const TABS = ['Tab 0', 'Tab 1', 'Tab 2'];
const TabView = (props?: Partial<TabSwitcherProps>): JSX.Element => (
  <TabSwitcher defaultTab={props?.defaultTab ?? TABS[0]} onChange={props?.onChange}>
    {TABS.map((tabId) => (
      <Tab key={tabId} id={tabId}>
        {tabId}
      </Tab>
    ))}
  </TabSwitcher>
);

describe('API Keys & Plugins - Tabs', () => {
  test('should render all tabs', () => {
    const { getByText } = render(<TabView />);
    TABS.forEach((tabId) => {
      expect(getByText(tabId)).toBeInTheDocument();
    });
  });

  test('should render default tab as active', () => {
    const { getByText } = render(<TabView defaultTab={TABS[1]} />);
    expect(getByText(TABS[1]).getAttribute('class')).toMatch(/active/i);
    expect(getByText(TABS[0]).getAttribute('class')).not.toMatch(/active/i);
    expect(getByText(TABS[2]).getAttribute('class')).not.toMatch(/active/i);
  });

  test('should call onChange with clicked tab', async () => {
    const onTabChange = jest.fn();
    const { getByText } = render(<TabView onChange={onTabChange} />);
    await userEvent.click(getByText(TABS[2]));
    expect(onTabChange).toHaveBeenCalledTimes(1);
    expect(onTabChange).toHaveBeenCalledWith(TABS[2]);
  });
});
