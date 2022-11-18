import React from 'react';
import Tabs from 'merchant/views/Settlements/v2/components/Tabs';
import { fireEvent, render, screen } from 'test-utils';
import * as SettlementsDB from 'merchant/views/Settlements/tests/data/SettlementsDB';

const handleTabChange = jest.fn();

test('should display all the tabs', () => {
  render(
    <Tabs
      activeTab="payment_domestic"
      handleTabChange={handleTabChange}
      breakupDetails={SettlementsDB.settlementTabBreakupDetails}
    />,
    {},
  );

  expect(screen.getByText(/Payment/i)).toBeInTheDocument();
  expect(screen.getByText(/Adjustment/i)).toBeInTheDocument();
});

test('should display sanitized tab names', () => {
  render(
    <Tabs
      activeTab="payment_domestic"
      handleTabChange={handleTabChange}
      breakupDetails={SettlementsDB.settlementTabBreakupDetails}
    />,
    {},
  );

  expect(screen.getByText(/Payment/i).parentElement).toHaveTextContent('Payment (204)');
  expect(screen.getByText(/Adjustment/i).parentElement).toHaveTextContent('Adjustment (16)');
  expect(screen.getByText(/Refund/i).parentElement).toHaveTextContent('Refund (8)');
});

test('should display sanitized tab names', () => {
  render(
    <Tabs
      activeTab="payment_domestic"
      handleTabChange={handleTabChange}
      breakupDetails={SettlementsDB.settlementTabBreakupDetails}
    />,
    {},
  );

  fireEvent.click(screen.getByText(/Payment/i));
  expect(handleTabChange).toBeCalledTimes(1);
});

test('should change active tab on click', async () => {
  const App: React.FC = () => {
    const [activeTab, setActiveTab] = React.useState('payment_domenstic');

    const handleActiveTabChange = (e: MouseEvent) => {
      return setActiveTab((e?.currentTarget as HTMLElement)?.innerHTML?.toLowerCase());
    };

    return (
      <Tabs
        activeTab={activeTab}
        handleTabChange={handleActiveTabChange}
        breakupDetails={SettlementsDB.settlementTabBreakupDetails}
      />
    );
  };
  render(<App />, {});

  expect(screen.getByText(/Payment/i).parentElement).toHaveClass('active');
  fireEvent.click(screen.getByText(/Adjustment/i));
  expect((await screen.findByText(/Adjustment/i)).parentElement).toHaveClass('active');
});
