import React from 'react';
import Pagination from 'merchant/views/Settlements/v2/components/Pagination';
import { screen, render, userEvent } from 'test-utils';
import * as SettlementsDB from 'merchant/views/Settlements/__test__/data/SettlementsDB';

const defaultProps = {
  next: jest.fn(),
  prev: jest.fn(),
  listData: SettlementsDB.settlementsListData,
  count: SettlementsDB.settlementsListData.length,
  skip: 0,
};

describe('Settlements Pagination', () => {
  const renderApp = (props) => render(<Pagination {...defaultProps} {...props} />);

  test('should show page numbers, previous and next buttons', () => {
    renderApp();
    expect(screen.getByText('Showing 1 - 10')).toBeInTheDocument();
    expect(screen.getByRole('button', { name: /previous/i })).toBeInTheDocument();
    expect(screen.getByRole('button', { name: /next/i })).toBeInTheDocument();
  });

  test('should disable previous button on first page ', () => {
    renderApp();
    expect(screen.getByRole('button', { name: /previous/i })).toBeDisabled();
  });

  test('should enable previous button other than first page and trigger prev callback on clicking previous', async () => {
    renderApp({ skip: 1 });
    const prevButton = screen.getByRole('button', { name: /previous/i });
    expect(prevButton).toBeEnabled();
    await userEvent.click(prevButton);
    expect(defaultProps.prev).toHaveBeenCalled();
  });

  test('should disable next button when list length is less than total count ', () => {
    renderApp({ listData: SettlementsDB.settlementsListData.slice(0, 5) });
    expect(screen.getByRole('button', { name: /next/i })).toBeDisabled();
  });

  test('should enable next button when list length is greater than total count and trigger next callback on clicking next', async () => {
    renderApp();
    const nextButton = screen.getByRole('button', { name: /next/i });
    expect(nextButton).toBeEnabled();
    await userEvent.click(nextButton);
    expect(defaultProps.next).toHaveBeenCalled();
  });

  describe('with showActualValues true', () => {
    test('should show actual range', () => {
      renderApp({
        showActualValues: true,
        listData: [1, 2, 3],
      });
      expect(screen.getByText('Showing 1 - 3')).toBeInTheDocument();
      expect(screen.getByRole('button', { name: /previous/i })).toBeInTheDocument();
      expect(screen.getByRole('button', { name: /next/i })).toBeInTheDocument();
    });

    test('should show no records found when count is 0', () => {
      renderApp({
        showActualValues: true,
        listData: [],
      });
      expect(screen.getByText('No records found')).toBeInTheDocument();
    });
  });
});
