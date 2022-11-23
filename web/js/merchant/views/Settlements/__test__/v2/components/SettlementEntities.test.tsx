import * as SettlementActions from 'merchant/reducers/settlements/details';
import React, { useEffect } from 'react';
import SettlementEntities from 'merchant/views/Settlements/v2/components/SettlementEntities';
import { connect } from 'react-redux';
import * as SettlementsDB from 'merchant/views/Settlements/__test__/data/SettlementsDB';

import {
  screen,
  render,
  waitFor,
  waitForLoadingToFinish,
  fireEvent,
  waitForElementToBeRemoved,
} from 'test-utils';

type AppProps = {
  fetchBreakupDetails?: ({ id: string }) => Record<string, unknown>;
};

let App: React.FC<AppProps> = (props: AppProps) => {
  useEffect(() => {
    props.fetchBreakupDetails?.({
      id: 'settlmenttest',
    });
  }, [props.fetchBreakupDetails]);

  return <SettlementEntities settlementId="settlmenttest" />;
};
App = connect(null, { ...SettlementActions })(App);

const waitForAllLoadingToFinish = async () => {
  await waitForLoadingToFinish();
  try {
    await waitForAllLoadingToFinish();
  } catch (error) {
    Promise.resolve();
  }
};

test('should load entities table and show correct headers', async () => {
  render(<App />, {});

  await waitForAllLoadingToFinish();

  expect(screen.getByText(/^Id$/i)).toBeInTheDocument();
  expect(screen.getByText(/^Amount$/i)).toBeInTheDocument();
  expect(screen.getByText(/^Fee$/i)).toBeInTheDocument();
  expect(screen.getByText(/^Tax$/i)).toBeInTheDocument();
  expect(screen.getByText(/^Created At$/i)).toBeInTheDocument();
  expect(screen.getByText(/^International$/i)).toBeInTheDocument();
  expect(screen.getByText(/^Status$/i)).toBeInTheDocument();

  expect(screen.getByText(/Showing/)).toHaveTextContent('Showing 1 - 10');
});

test('should have correct no of rows in entities table', async () => {
  render(<App />, {});

  await waitForAllLoadingToFinish();
  const count = parseInt((document.getElementsByName('count')[0] as HTMLInputElement).value, 10);

  expect(screen.getAllByText(/^pay_/i)).toHaveLength(
    Math.min(SettlementsDB.settlementsListData.length, count),
  );
});

test('should clear input fields on clicking clear', async () => {
  render(<App />, {});

  await waitForAllLoadingToFinish();

  const countInput = document.getElementsByName('count')[0];
  const count = parseInt((countInput as HTMLInputElement).value, 10);

  expect(count).toBe(10);

  fireEvent.change(countInput, {
    target: {
      value: '5',
    },
  });

  fireEvent.click(screen.getByText(/search/i));
  await waitForAllLoadingToFinish();

  fireEvent.click(screen.getByText(/clear/i));
  await waitFor(() => expect(count).toBe(10));
});

test('should have correct no of rows in entities table', async () => {
  render(<App />, {});

  await waitForAllLoadingToFinish();
  const countInput = document.getElementsByName('count')[0];

  fireEvent.change(countInput, {
    target: {
      value: '5',
    },
  });

  fireEvent.click(screen.getByText(/search/i));
  await waitForAllLoadingToFinish();

  expect(screen.getByText(/Showing/)).toHaveTextContent('Showing 1 - 5');

  expect(screen.getAllByText(/^pay_/i)).toHaveLength(
    Math.min(SettlementsDB.settlementsListData.length, 5),
  );
});

test('should show correct data on switching to refund tab', async () => {
  render(<App />, {});

  await waitForAllLoadingToFinish();
  fireEvent.click(screen.getByText(/Refund/i));
  await waitFor(() => expect(screen.queryByText(/^Refund$/i)?.parentElement).toHaveClass('active'));
  await waitForElementToBeRemoved(screen.getAllByText(/^pay_/i)[0]);

  expect(screen.getByText(/Showing/)).toHaveTextContent('Showing 1 - 10');

  expect(screen.getAllByText(/^ref_/i)).toHaveLength(
    Math.min(SettlementsDB.settlementsListRefundData.length, 10),
  );
});

test('should show tooltip on mouseenter on amount', async () => {
  const { container } = render(<App />, {});

  await waitForAllLoadingToFinish();
  const amountTooltipContainer = container.querySelector('.rzp-amount');
  const amountTooltipElem = amountTooltipContainer?.nextElementSibling;

  expect(amountTooltipElem).not.toHaveClass('show');
  fireEvent.mouseEnter(amountTooltipContainer?.parentElement as HTMLElement);

  await waitFor(() => {
    expect(amountTooltipElem).toHaveClass('show');
  });
});
