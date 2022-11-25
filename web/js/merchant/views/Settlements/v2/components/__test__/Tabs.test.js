import React from 'react';
import Tabs from 'merchant/views/Settlements/v2/components/Tabs';
import { userEvent, render, screen, waitFor } from 'test-utils';
import * as SettlementsDB from 'merchant/views/Settlements/tests/data/SettlementsDB';
import { titleCase } from 'common/utils/rzp-utils';
import { sanitizeTabName } from 'merchant/views/Settlements/v2/util';

const breakupDetails = SettlementsDB.settlementTabBreakupDetails;
const defaultProps = {
  breakupDetails,
  handleTabChange: jest.fn(),
  activeTab: breakupDetails.items[0].component,
};

const tabItems = breakupDetails.items.map((tabItem) => ({
  component: tabItem.component,
  name: titleCase(sanitizeTabName(tabItem.component)),
  count: tabItem.count,
}));

describe('Tabs', () => {
  const renderApp = (props) => render(<Tabs {...defaultProps} {...props} />);

  test('should render all the tab names with count', () => {
    renderApp();
    tabItems.forEach((tabItem) => {
      const tabElement = screen.getByText(tabItem.name);
      expect(tabElement).toBeInTheDocument();
      expect(tabElement.parentElement).toHaveTextContent(`${tabItem.name} (${tabItem.count})`);
    });
  });

  test('should call handleTabChange when tab is clicked', async () => {
    renderApp();
    const tabElement = screen.getByText(tabItems[0].name);
    await userEvent.click(tabElement);
    await waitFor(() => {
      expect(defaultProps.handleTabChange).toHaveBeenCalled();
    });
  });

  test('should render active tab with active class', () => {
    const activeTabInfo = tabItems[1];
    renderApp({ activeTab: activeTabInfo.component });
    const tabElement = screen.getByText(activeTabInfo.name);
    expect(tabElement.parentElement).toHaveClass('active');
  });

  test("should count be added if there're two items with same component", () => {
    renderApp({
      breakupDetails: {
        ...breakupDetails,
        items: [...breakupDetails.items, breakupDetails.items[0]],
      },
    });
    const tabElement = screen.getByText(tabItems[0].name);
    expect(tabElement.parentElement).toHaveTextContent(
      `${tabItems[0].name} (${tabItems[0].count * 2})`,
    );
  });

  test('should not show tab if the count is 0', () => {
    renderApp({
      breakupDetails: {
        ...breakupDetails,
        items: [{ ...breakupDetails.items[0], count: 0 }, ...breakupDetails.items.slice(1)],
      },
    });
    expect(screen.queryByText(tabItems[0].name)).not.toBeInTheDocument();
  });
});
