import React from 'react';
import { useQuery } from '@tanstack/react-query';

import { render, screen, waitFor, within } from 'test-utils';

import { rewardsHistory, expectedRewards } from './mocks';
import RewardsHistory from '../RewardsHistory';

jest.mock('@tanstack/react-query', () => {
  const actualReactQuery = jest.requireActual('@tanstack/react-query');
  return {
    ...actualReactQuery,
    useQuery: jest.fn(),
  };
});

describe('RewardsHistory - Exporter Rewards', () => {
  beforeEach(() => {
    (useQuery as jest.Mock).mockReturnValue({ isLoading: false, isError: false, data: undefined });
    console.error = jest.fn(); // Silence error messages
    console.warn = jest.fn(); // Silence warning messages
  });

  afterEach(() => {
    jest.clearAllMocks();
  });

  const renderComponent = (props = {}) => {
    return render(<RewardsHistory {...props} />);
  };

  test('renders without error', () => {
    renderComponent();
    expect(screen.getByText('Previous milestones and rewards')).toBeVisible();
  });

  test('assert table headers', () => {
    renderComponent();

    const expected = [
      'Cycle Period',
      'GMV achieved',
      'Reward',
      'Disbursal date',
      'Milestone',
      'Reward Status',
    ];

    const headers = screen.getAllByRole('columnheader');

    // Extract the text content from each header
    const results = headers.map((header) => within(header).getByText(/.+/).textContent);
    expect(results).toEqual(expected);
  });

  test('assert table rows', async () => {
    (useQuery as jest.Mock).mockReturnValue({
      isLoading: false,
      isError: false,
      data: rewardsHistory,
    });

    renderComponent();

    await waitFor(() => {
      const rows = screen.getAllByRole('row');
      expect(rows).toHaveLength(expectedRewards.length);

      rows.forEach((row, rowIndex) => {
        const cells = within(row).getAllByRole('cell');
        expect(cells).toHaveLength(expectedRewards[rowIndex].length);

        cells.forEach((cell, cellIndex) => {
          expect(cell.textContent).toContain(expectedRewards[rowIndex][cellIndex]);
        });
      });
    });
  });

  test('assert table loading rows', async () => {
    (useQuery as jest.Mock).mockReturnValue({
      isLoading: true,
      isError: false,
      data: undefined,
    });

    renderComponent();

    await waitFor(() => {
      const rows = screen.getAllByRole('row');
      expect(rows).toHaveLength(10);
    });
  });
});
