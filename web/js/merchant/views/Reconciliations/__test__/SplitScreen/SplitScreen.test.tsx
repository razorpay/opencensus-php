import React from 'react';
import { render, screen, waitFor, userEvent, act } from 'test-utils';
import SplitScreen from 'merchant/views/Reconciliations/SplitScreen/index';

jest.mock('merchant/views/Reconciliations/SplitScreen/usedrag', () => ({
  useDrag: () => ({
    dynamicWidth: 50,
    handleDragMouseDown: jest.fn(),
  }),
}));

const renderSplitScreen = (props = {}) => {
  render(<SplitScreen {...props} />);
};

describe('SplitScreen', () => {
  test('should render the SplitScreen without throwing errors', async () => {
    await waitFor(() => {
      expect(() => renderSplitScreen()).not.toThrow();
    });
  });

  test('should render date range picker inputs', async () => {
    renderSplitScreen();
    await waitFor(() => {
      expect(screen.getByLabelText('Start Date')).toBeInTheDocument();
      expect(screen.getByLabelText('End Date')).toBeInTheDocument();
    });
  });

  test('should open filter modal when filter button is clicked', async () => {
    renderSplitScreen();
    const filterButton = await screen.findByLabelText('Advance Filters');

    await userEvent.click(filterButton);
    expect(screen.getByRole('dialog')).toBeInTheDocument();
  });

  test('should render export button', async () => {
    renderSplitScreen();
    await waitFor(() => {
      expect(screen.getByText('Export View')).toBeInTheDocument();
    });
  });

  test('should render run ID dropdown', async () => {
    renderSplitScreen();
    await waitFor(() => {
      const dropdownButton = screen.getByRole('combobox', { name: 'Run ID' });
      expect(dropdownButton).toBeInTheDocument();
    });
  });

  test('should show tooltip on filter button hover', async () => {
    render(<SplitScreen />);

    const filterButton = screen.getByRole('button', { name: /filters/i });
    expect(filterButton).toBeInTheDocument();
    act(() => {
      userEvent.hover(filterButton);
    });
    const tooltip = await screen.findByText(/advance filters/i);
    expect(tooltip).toBeInTheDocument();
    act(() => {
      userEvent.unhover(filterButton);
    });
  });
});
