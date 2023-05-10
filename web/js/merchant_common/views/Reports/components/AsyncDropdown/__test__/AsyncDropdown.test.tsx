import React, { useState } from 'react';
import { render, screen, userEvent } from 'test-utils';
import { AsyncDropdown } from 'merchant_common/views/Reports/components';
import * as API from './fixtures/index';

const apiSpy = jest.spyOn(API, 'apiCall');

const App = (props) => {
  const [selected, setSelected] = useState<string[]>();

  const renderDropdown = (allProps) => {
    return <AsyncDropdown {...allProps} promise={API.apiCall} parseData={(data) => data} />;
  };
  return (
    <>
      <div aria-label="selected value">
        {Array.isArray(selected) ? selected.join(',') : selected}
      </div>
      {renderDropdown({
        helpText: 'Testing dropdown',
        label: 'Dropdown Field For Test',
        placeHolder: 'Place holder for dropdown',
        value: selected,
        onChange: setSelected,
        ariaLabelBy: 'Dropdown input field',
        shouldAllowMultiple: true,
        ...props,
      })}
    </>
  );
};

describe('Async Dropdown', () => {
  test('should render children correctly', () => {
    render(<App />);
    expect(screen.getByText('Testing dropdown')).toBeInTheDocument();
    expect(screen.getByText('Dropdown Field For Test')).toBeInTheDocument();
    expect(screen.getByPlaceholderText('Place holder for dropdown')).toBeInTheDocument();
  });

  test('should show resolved options in the dropdown on search', async () => {
    render(<App />);
    expect(screen.getByText('Testing dropdown')).toBeInTheDocument();
    await userEvent.click(screen.getByLabelText('Dropdown input field'));
    await userEvent.type(screen.getByLabelText('Search An Item Here'), 'TEST1');
    expect(screen.getByLabelText(`Loading... Please wait...`)).toBeInTheDocument();
  });

  test('should show resolved options in the dropdown on search', async () => {
    render(<App debounceInterval={0} shouldAllowMultiple defaultValue={['React']} />);
    expect(screen.getByText('Testing dropdown')).toBeInTheDocument();
    await userEvent.click(screen.getByLabelText('Dropdown input field'));
    await userEvent.type(screen.getByLabelText('Search An Item Here'), 'TEST1');
    expect(screen.queryByLabelText(`Loading... Please wait...`)).not.toBeInTheDocument();
    await expect(apiSpy).toHaveBeenCalled();
    await userEvent.click(screen.getByLabelText('TEST1'));
    expect(screen.getByLabelText(`Remove Selected Option -> TEST1`)).toBeInTheDocument();
    await userEvent.click(screen.getByLabelText('selected value'));
    expect(screen.getByLabelText('selected value').innerHTML).toEqual('React,TEST1');
  });

  test('should show resolved options in the dropdown on search', async () => {
    // just throwing an error
    apiSpy.mockResolvedValue(new Error('Error was thrown') as unknown as string[]);
    render(<App debounceInterval={0} shouldAllowMultiple />);
    expect(screen.getByText('Testing dropdown')).toBeInTheDocument();
    await userEvent.click(screen.getByLabelText('Dropdown input field'));
    await userEvent.type(screen.getByLabelText('Search An Item Here'), 'TEST1');
    await expect(apiSpy).toHaveBeenCalled();
    await userEvent.clear(screen.getByLabelText('Search An Item Here'));
    expect(screen.getByText('Testing dropdown')).toBeInTheDocument();
  });
});
