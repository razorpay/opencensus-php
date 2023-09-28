import React from 'react';

import { render, screen, userEvent } from 'common/services/test/test-utils';

import { age, gender, name, tableData } from './mocks/fixtures';
import MagicDataTable from '..';

const renderTable = (newProps = {}) => {
  render(<MagicDataTable data={tableData} columns={[name, gender, age]} {...newProps} />);
};

describe('Datatable', () => {
  test('Should render data table with mock data', () => {
    renderTable();

    const name = screen.queryByText(/Akash/i);
    const gender = screen.queryByText('male');
    const age = screen.getByText(/25/i);

    expect(name).toBeInTheDocument();
    expect(gender).toBeInTheDocument();
    expect(age).toBeInTheDocument();
    expect(screen.queryByText('+ Create more')).not.toBeInTheDocument();
  });
  test('Should render data table with add more button', async () => {
    const handleAddMore = jest.fn();
    renderTable({
      addMoreLabel: 'test',
      handleAddMore,
    });

    const name = screen.queryByText(/Akash/i);
    const gender = screen.queryByText('male');
    const age = screen.getByText(/25/i);
    const createButton = screen.getByTestId('magic-add-more-button');

    expect(name).toBeInTheDocument();
    expect(gender).toBeInTheDocument();
    expect(age).toBeInTheDocument();
    expect(createButton).toBeInTheDocument();
    await userEvent.click(createButton);
    expect(handleAddMore).toHaveBeenCalled();
  });
});
