import React from 'react';

import { screen, render, userEvent } from 'test-utils';

import BrandsTableComponent from 'merchant/views/BillMeSettings/BrandsAndTerminals/containers/BrandsTableContainer/components/BrandsTableComponent/BrandsTableComponent';
import { BRANDS_DATA } from 'merchant/views/BillMeSettings/BrandsAndTerminals/containers/BrandsTableContainer/components/BrandsTableComponent/__tests__/mocks';

const App = ({ props }) => <BrandsTableComponent {...props} />;

describe('BrandsTableComponent', () => {
  test("should render 'BrandsTableComponent' component as expected", async () => {
    const changePage = jest.fn();
    const changePageSize = jest.fn();
    const onBrandNameClick = jest.fn();
    const onAddNewBrandClick = jest.fn();
    const props = {
      tableProps: {
        changePage,
        changePageSize,
        isRefreshing: false,
        defaultPageSize: 10,
        totalItemCount: 100,
        brandsData: BRANDS_DATA,
      },
      onBrandNameClick,
      onAddNewBrandClick,
    };

    render(<App props={props} />);

    // Add New Brand CTA
    const addNewBrandBtn = screen.getByRole('button', { name: 'New Brand' });
    expect(addNewBrandBtn).toBeInTheDocument();
    await userEvent.click(addNewBrandBtn);
    expect(onAddNewBrandClick).toHaveBeenCalledTimes(1);

    // Brands table info
    // Table header row
    expect(screen.getByText('Name')).toBeInTheDocument();
    expect(screen.getByText('Description')).toBeInTheDocument();

    // Table value row
    const brandNameCell = screen.getByText(BRANDS_DATA[0].name);
    expect(brandNameCell).toBeInTheDocument();
    await userEvent.click(screen.getByRole('button', { name: BRANDS_DATA[0].name }));
    expect(onBrandNameClick).toHaveBeenCalledWith(BRANDS_DATA[0].id);
    expect(screen.getByText(BRANDS_DATA[0].description)).toBeInTheDocument();

    // 'Description' cell fallback value validation when not present
    expect(screen.getByText('-')).toBeInTheDocument();

    // Page number change
    await userEvent.click(screen.getByText(2));
    expect(changePage).toHaveBeenCalledWith(10);

    // Page size change
    const pageSizePicker = screen.getByRole('combobox');
    await userEvent.click(pageSizePicker);
    await userEvent.click(screen.getByRole('option', { name: '25' }));
    expect(changePageSize).toHaveBeenCalledWith(25);
  });

  test('should render placeholder content when no Brand records are available', () => {
    const props = {
      tableProps: {
        isRefreshing: false,
        defaultPageSize: 10,
        totalItemCount: 0,
        brandsData: [],
      },
    };

    render(<App props={props} />);

    // Table placeholder content
    expect(
      screen.getByText('No Brands found. Click on Add New Brand to create one.'),
    ).toBeInTheDocument();
  });
});
