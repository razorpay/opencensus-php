import React from 'react';
import Pagination from 'merchant/views/Settlements/v2/components/Pagination';
import { screen, render, fireEvent } from 'test-utils';

import * as SettlementsDB from 'merchant/views/Settlements/__test__/data/SettlementsDB';

const next = jest.fn();
const prev = jest.fn();

// TODO: Replace with Type from file
type PaginationProps = {
  skip?: number;
};

const App: React.FC<PaginationProps> = (props: PaginationProps) => (
  <Pagination
    listData={SettlementsDB.settlementsListData}
    count={SettlementsDB.settlementsListData.length}
    skip={0}
    next={next}
    prev={prev}
    {...props}
  />
);

test('should show page numbers', () => {
  render(<App />, {});

  expect(screen.getByText(/Showing/)).toHaveTextContent('Showing 1 - 10');
});

test('should disable previous button on first page', () => {
  render(<App />, {});

  expect(screen.getByText(/Showing/)).toHaveTextContent('Showing 1 - 10');

  expect(screen.getByRole('button', { name: /previous/i })).toBeDisabled();
});

test('should trigger prev function on clicking previous', () => {
  render(<App skip={1} />, {});

  fireEvent.click(screen.getByRole('button', { name: /previous/i }));
  expect(prev).toBeCalledTimes(1);
});

test('should trigger next function on clicking previous', () => {
  render(<App skip={1} />, {});

  fireEvent.click(screen.getByRole('button', { name: /next/i }));
  expect(next).toBeCalledTimes(1);
});
