import React from 'react';

import {
  DesktopFiltering,
  MobileFiltering,
} from 'merchant/views/RizeMarketplace/Marketplace/components/Filtering';
import { FilteringProps } from 'merchant/views/RizeMarketplace/Marketplace/components/Filtering/types';
import { MARKETPLACE_CATEGORIES } from 'merchant/views/RizeMarketplace/common/constants';
import { render, screen, userEvent, waitFor } from 'test-utils';

const renderDesktopFiltering = (props: FilteringProps) => render(<DesktopFiltering {...props} />);

const renderMobileFiltering = (props: FilteringProps) => render(<MobileFiltering {...props} />);

const getFilterCheckboxes = () =>
  screen.getAllByRole('checkbox', {
    hidden: true,
  });

describe('DesktopFiltering', () => {
  const getClearAllButton = () =>
    screen.getByRole('button', {
      name: /clear all/i,
      hidden: true,
    });

  test('should disable "clear all" button when no filters are selected', () => {
    const triggerFiltering = jest.fn();
    renderDesktopFiltering({ value: [], onChange: triggerFiltering });

    const clearAllButton = getClearAllButton();
    expect(clearAllButton).toBeDisabled();
  });

  test('should clear all filters when "clear all" button is clicked', async () => {
    const triggerFiltering = jest.fn();
    renderDesktopFiltering({
      value: [MARKETPLACE_CATEGORIES[0], MARKETPLACE_CATEGORIES[1]],
      onChange: triggerFiltering,
    });

    const clearAllButton = getClearAllButton();
    expect(clearAllButton).toBeEnabled();

    await userEvent.click(clearAllButton);
    expect(triggerFiltering).toBeCalledWith([]);
  });

  test('should trigger filtering when a category is selected', async () => {
    const triggerFiltering = jest.fn();
    renderDesktopFiltering({ value: [], onChange: triggerFiltering });

    const filters = getFilterCheckboxes();
    await userEvent.click(filters[0]);

    expect(triggerFiltering).toBeCalledWith([MARKETPLACE_CATEGORIES[0]]);
  });
});

describe('MobileFiltering', () => {
  const getFilterButton = () => screen.getByRole('button', { name: /filter/i });

  const getApplyFiltersButton = () => screen.queryByRole('button', { name: /apply filters/i });

  const getClearAllButton = () =>
    screen.getByRole('button', {
      name: /clear filters/i,
    });

  const openMobileFilteringBottomSheet = async () => {
    const filterButton = getFilterButton();
    await userEvent.click(filterButton);

    await waitFor(() => {
      getApplyFiltersButton();
    });
  };

  test('should show filters bottom sheet when clicking on "filter" button', async () => {
    const triggerFiltering = jest.fn();
    renderMobileFiltering({ value: [], onChange: triggerFiltering });

    let applyFiltersButton = getApplyFiltersButton();
    expect(applyFiltersButton).not.toBeInTheDocument();

    const filterButton = getFilterButton();
    await userEvent.click(filterButton);

    await waitFor(() => {
      applyFiltersButton = getApplyFiltersButton();
      expect(applyFiltersButton).toBeVisible();
    });
  });

  test('should disable "clear all" button when no filters are selected', async () => {
    const triggerFiltering = jest.fn();
    renderMobileFiltering({ value: [], onChange: triggerFiltering });

    await openMobileFilteringBottomSheet();

    const clearAllButton = getClearAllButton();
    expect(clearAllButton).toBeDisabled();
  });

  test('should clear all filters when "clear all" button is clicked', async () => {
    const triggerFiltering = jest.fn();
    renderMobileFiltering({
      value: [MARKETPLACE_CATEGORIES[0], MARKETPLACE_CATEGORIES[1]],
      onChange: triggerFiltering,
    });

    await openMobileFilteringBottomSheet();

    const clearAllButton = getClearAllButton();
    expect(clearAllButton).toBeEnabled();

    await userEvent.click(clearAllButton);
    expect(triggerFiltering).toBeCalledWith([]);
  });

  test('should flush local changes in filters bottom sheet only when "apply filters" button is clicked', async () => {
    const triggerFiltering = jest.fn();
    renderMobileFiltering({ value: [], onChange: triggerFiltering });

    await openMobileFilteringBottomSheet();

    const filters = getFilterCheckboxes();
    await userEvent.click(filters[0]);

    expect(triggerFiltering).not.toHaveBeenCalled();

    const applyFiltersButton = getApplyFiltersButton();
    if (!applyFiltersButton) return;

    await userEvent.click(applyFiltersButton);
    expect(triggerFiltering).toHaveBeenCalledWith([MARKETPLACE_CATEGORIES[0]]);
  });
});
