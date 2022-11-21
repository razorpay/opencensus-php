import React from 'react';
import { fireEvent, render, screen, delay } from 'test-utils';
import HolidayModal from 'merchant/views/Settlements/Settlements/components/Modals/HolidayModal';
import { holidayList } from 'merchant/views/Settlements/Settlements/components/Modals/__test__/mocks/fixtures/HolidayModal';

const renderApp = (list) =>
  render(<HolidayModal holidayList={list} />, {
    showModal: true,
  });

test('should render empty state properly', () => {
  renderApp([]);

  expect(screen.getByText(/No data found!/i)).toBeInTheDocument();
});

test('should render heading and action button, holiday list and close on clicking closeModal', async () => {
  const { container } = renderApp(holidayList);

  expect(screen.getByText(/Date/i)).toBeInTheDocument();
  expect(screen.getByText(/Name/i)).toBeInTheDocument();

  const button = screen.getByRole('button');
  fireEvent.click(button);

  expect(screen.getByText(/Date/i)).toBeInTheDocument();
  expect(screen.getByText(/Name/i)).toBeInTheDocument();

  await delay();
  const rowsLength = container.getElementsByTagName('tr').length;
  expect(rowsLength).toBe(holidayList.data[2022].length + 1);
});
