import * as SettlementActions from 'merchant/reducers/settlements/details';
import React, { useEffect } from 'react';
import SettlementSchedule from 'merchant/views/Settlements/Settlements/components/SettlementSchedule';
import { analyticsTrack } from 'common/utils/analytics';
import { delay, fireEvent, render, screen, waitFor } from 'test-utils';
import { connect } from 'react-redux';
import TestModal from 'common/services/test/TestModal';

type Obj = Record<string, unknown>;

type AppProps = {
  fetchSchedule?: () => Obj;
  schedule?: Obj;
};

let App: React.FC<AppProps> = (props: AppProps) => {
  useEffect(() => {
    props.fetchSchedule?.();
  }, [props.fetchSchedule]);

  const scheduleData = props.schedule?.data as Array<Obj>;

  return scheduleData?.length ? <TestModal component={<SettlementSchedule />} /> : null;
};
App = connect((state) => state.settlement, { ...SettlementActions })(App);

test('should render default modal texts', async () => {
  render(<App />, { showModal: true });

  await delay();

  expect(screen.getByText(/Settlement Cycle/i)).toBeInTheDocument();
  expect(screen.getByText(/Your payments get settled to your account in,/i)).toBeInTheDocument();
  expect(screen.getByText(/for Default Schedules/i)).toBeInTheDocument();
  expect(screen.getByText(/T is the date of payment capture/i)).toBeInTheDocument();
  expect(screen.getByText(/Weekends aren’t counted as working days./i)).toBeInTheDocument();
});

test('should render domestic and other payment schedules', async () => {
  render(<App />, { showModal: true });

  await delay();

  expect(screen.getByText(/Domestic Payments/i).parentElement).toHaveTextContent(
    /Domestic Payments\*T\+0 working days/i,
  );
  expect(screen.getByText(/Other method specific Settlement schedules/i)).toBeInTheDocument();
  expect(screen.getByText(/Netbanking/i).parentElement).toHaveTextContent(
    /Netbanking \(Domestic\)T\+24 working days/i,
  );
});

test('toggle example schedule', async () => {
  render(<App />, { showModal: true });

  await delay();

  fireEvent.click(screen.getByText(/Examples/i));

  expect(await screen.findByText(/Following is an example for T\+2 Days/)).toBeInTheDocument();
  expect(await screen.findByRole('img')).toBeInTheDocument();

  fireEvent.click(screen.getByText(/Examples/i));
  expect(screen.queryByText(/Following is an example for T\+2 Days/)).not.toBeInTheDocument();
});

test('trigger analytics on clicking settlement guide', async () => {
  render(<App />, { showModal: true });

  await delay();

  fireEvent.click(screen.getByRole('button', { name: 'Settlement Guide' }));

  expect(window.rzpAnalytics).toBeCalledTimes(1);
  expect(analyticsTrack).toBeCalledTimes(1);
});

test('open holidays modal on clicking bank holidays', async () => {
  render(<App />, { showModal: true });

  await delay();

  fireEvent.click(screen.getByRole('button', { name: 'Bank Holidays' }));

  expect(window.rzpAnalytics).toBeCalledTimes(1);
  expect(analyticsTrack).toBeCalledTimes(1);

  await waitFor(() => expect(screen.getByText(/Holidays List/i)).toBeInTheDocument());
});

test('should close modal on clicking close icon', async () => {
  render(<App />, { showModal: true });

  await delay();

  const closeButton = screen.getByRole('button', { name: '' });

  fireEvent.click(closeButton);

  expect(screen.queryByText(/Settlement Cycle/i)).not.toBeInTheDocument();
  expect(
    screen.queryByText(/Your payments get settled to your account in,/i),
  ).not.toBeInTheDocument();
  expect(screen.queryByText(/for Default Schedules/i)).not.toBeInTheDocument();
  expect(screen.queryByText(/T is the date of payment capture/i)).not.toBeInTheDocument();
  expect(screen.queryByText(/Weekends aren’t counted as working days./i)).not.toBeInTheDocument();
});
