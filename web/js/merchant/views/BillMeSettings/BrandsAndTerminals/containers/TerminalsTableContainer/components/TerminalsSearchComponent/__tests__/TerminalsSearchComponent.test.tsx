import React from 'react';

import {
  TERMINAL_STATUS_OPTIONS,
  TERMINALS_SEARCH_BY_FIELDS,
} from 'merchant/views/BillMeSettings/BrandsAndTerminals/containers/TerminalsTableContainer/constants';
import TerminalsSearchComponent from 'merchant/views/BillMeSettings/BrandsAndTerminals/containers/TerminalsTableContainer/components/TerminalsSearchComponent';
import { screen, render, fireEvent } from 'test-utils';

const App = ({ props }) => <TerminalsSearchComponent {...props} />;

describe('TerminalsSearchComponent', () => {
  test("should render 'TerminalsSearchComponent' filters as expected", () => {
    const setTerminalsFilterStatus = jest.fn();
    const setTerminalsFilterSearch = jest.fn();
    const fetchFilteredTerminalsData = jest.fn();
    const setTerminalsSearchByColumn = jest.fn();
    const setTerminalsFilterOffset = jest.fn();

    const props = {
      statusProps: {
        selectedTerminalsStatus: [TERMINAL_STATUS_OPTIONS.OFF.value],
        setTerminalsFilterStatus,
      },
      searchColumnProps: { selectedSearchByColumn: 'TERMINAL_NAME', setTerminalsSearchByColumn },
      searchProps: { searchTerm: 'Test Search Term', setTerminalsFilterSearch },
      paginationProps: { offset: 0, setTerminalsFilterOffset },
      fetchFilteredTerminalsData,
    };

    render(<App props={props} />);
    const comboBoxes = screen.getAllByRole('combobox');

    // Status
    expect(screen.getByText('Status')).toBeInTheDocument();
    fireEvent.click(comboBoxes[0]);
    expect(screen.getAllByRole('option')).toHaveLength(3);
    expect(
      screen.getByRole('option', { name: TERMINAL_STATUS_OPTIONS.ALL.label }),
    ).toBeInTheDocument();
    expect(
      screen.getByRole('option', { name: TERMINAL_STATUS_OPTIONS.ON.label }),
    ).toBeInTheDocument();
    expect(
      screen.getByRole('option', { name: TERMINAL_STATUS_OPTIONS.OFF.label }),
    ).toBeInTheDocument();
    fireEvent.click(screen.getByRole('option', { name: TERMINAL_STATUS_OPTIONS.ON.label }));
    expect(setTerminalsFilterStatus).toHaveBeenCalledWith(TERMINAL_STATUS_OPTIONS.ON.value);

    // Search By field
    expect(screen.getByText('Search By')).toBeInTheDocument();
    fireEvent.click(comboBoxes[1]);
    expect(screen.getAllByRole('option')).toHaveLength(7);
    expect(
      screen.getByRole('option', { name: TERMINALS_SEARCH_BY_FIELDS.TERMINAL_NAME.label }),
    ).toBeInTheDocument();
    expect(
      screen.getByRole('option', { name: TERMINALS_SEARCH_BY_FIELDS.IP_ADDRESS.label }),
    ).toBeInTheDocument();
    expect(
      screen.getByRole('option', { name: TERMINALS_SEARCH_BY_FIELDS.MAC_ADDRESS.label }),
    ).toBeInTheDocument();
    expect(
      screen.getByRole('option', { name: TERMINALS_SEARCH_BY_FIELDS.VERSION.label }),
    ).toBeInTheDocument();
    fireEvent.click(
      screen.getByRole('option', { name: TERMINALS_SEARCH_BY_FIELDS.MAC_ADDRESS.label }),
    );
    expect(setTerminalsSearchByColumn).toHaveBeenCalledWith(
      TERMINALS_SEARCH_BY_FIELDS.MAC_ADDRESS.value,
    );

    // Search field
    const searchField = screen.getByPlaceholderText('Search');
    expect(searchField).toBeInTheDocument();
    expect(searchField).toHaveValue('Test Search Term');
    fireEvent.change(searchField, { target: { value: 'test terminal name' } });
    expect(setTerminalsFilterSearch).toHaveBeenLastCalledWith('test terminal name');

    // Search icon
    const buttonFields = screen.getAllByRole('button');
    const searchIcon = buttonFields[buttonFields.length - 1];
    expect(searchIcon).toBeInTheDocument();
    fireEvent.click(searchIcon);
    expect(fetchFilteredTerminalsData).toHaveBeenCalledTimes(1);

    // 'Enter' key from Search field should trigger 'fetchFilteredTerminalsData'
    fireEvent.change(searchField, { target: { value: 'test terminal name 1' } });
    expect(setTerminalsFilterSearch).toHaveBeenLastCalledWith('test terminal name 1');
    fireEvent.submit(screen.getByTestId('search-field-form'));
    expect(fetchFilteredTerminalsData).toHaveBeenCalledTimes(2);
  });
});
