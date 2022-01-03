import React, { useEffect } from 'react';
import { connect } from 'react-redux';
import { fetchHolidayList } from 'merchant/reducers/settlements/details';
import { fireEvent, render, screen, delay } from 'test-utils';
import HolidayModal from 'merchant/views/Settlements/Settlements/components/Modals/HolidayModal';
import * as SettlementsDB from 'merchant/views/Settlements/tests/data/SettlementsDB';

type AppProps = {
  fetchHolidayList?: () => void;
  skipfetch?: boolean;
};

let App: React.FC<AppProps> = (props: AppProps) => {
  useEffect(() => {
    if (!props.skipfetch) props.fetchHolidayList?.();
  }, [props.fetchHolidayList, props.skipfetch]);

  return <HolidayModal />;
};
App = connect(null, { fetchHolidayList })(App);

test('should render empty state properly', () => {
  render(<App skipfetch />, {});

  expect(screen.getByText(/No data found!/i)).toBeInTheDocument();
});

test('should render heading and action button, holiday list and close on clicking closeModal', async () => {
  const { container } = render(<App />, {});

  expect(screen.getByText(/Date/i)).toBeInTheDocument();
  expect(screen.getByText(/Name/i)).toBeInTheDocument();

  const button = screen.getByRole('button');
  fireEvent.click(button);

  expect(screen.getByText(/Date/i)).toBeInTheDocument();
  expect(screen.getByText(/Name/i)).toBeInTheDocument();

  await delay();
  const rowsLength = container.getElementsByTagName('tr').length;

  expect(rowsLength).toBe(SettlementsDB.holidaysList[2022].length + 1);
});
