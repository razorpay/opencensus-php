import React, { useState } from 'react';
import { render, screen, userEvent } from 'test-utils';
import { BaseDropdown } from 'merchant_common/views/Reports/components/MultiSelectDropdown/_BaseDropdown';

const App = (props) => {
  const { children, ...other } = props;
  const [selected, setSelected] = useState<string | string[]>();
  const renderDropdown = (allProps) => {
    return <BaseDropdown {...allProps} />;
  };

  const handleChildren = () => {
    if (children && Array.isArray(selected)) {
      return children(selected);
    } else {
      return Array.isArray(selected) ? selected.join(',') : selected;
    }
  };

  return (
    <>
      <div aria-label="selected value">{handleChildren()}</div>
      {renderDropdown({
        helpText: 'Testing dropdown',
        label: 'Dropdown Field For Test',
        placeHolder: 'Place holder for dropdown',
        value: selected,
        onChange: setSelected,
        ariaLabelBy: 'Dropdown input field',
        ...other,
      })}
    </>
  );
};

// this will not cover async dropdown contexts, changes (will be done there)
describe('Dropdown', () => {
  test('should render children correctly', () => {
    render(<App />);
    expect(screen.getByText('Testing dropdown')).toBeInTheDocument();
    expect(screen.getByText('Dropdown Field For Test')).toBeInTheDocument();
    expect(screen.getByText('Place holder for dropdown')).toBeInTheDocument();
  });

  test('should open dropdown options', async () => {
    render(<App options={['TEST1', 'TEST2']} />);
    await userEvent.click(screen.getByLabelText('Dropdown input field'));
    expect(screen.queryByLabelText('TEST1')).toBeInTheDocument();
  });

  test('should update state on option click', async () => {
    render(<App options={['TEST1', 'TEST2']} />);
    await userEvent.click(screen.getByLabelText('Dropdown input field'));
    await userEvent.click(screen.getByLabelText('TEST1'));
    expect(screen.queryByLabelText('TEST1')).not.toBeInTheDocument();
    expect(screen.getByLabelText('selected value').innerHTML).toEqual('TEST1');
  });

  test('should close dropdown on outside click', async () => {
    render(<App options={['TEST1', 'TEST2']} />);
    await userEvent.click(screen.getByLabelText('selected value'));
    expect(screen.queryByLabelText('TEST1')).not.toBeInTheDocument();
  });

  test('should show a searchable input', async () => {
    render(<App options={['TEST1', 'TEST2']} isSearchable />);
    await userEvent.click(screen.getByLabelText('Dropdown input field'));
    expect(screen.queryByLabelText('Search An Item Here')).toBeInTheDocument();
  });

  test('should filter options on typing in search input', async () => {
    render(<App options={['TEST1', 'TEST2']} isSearchable />);
    await userEvent.click(screen.getByLabelText('Dropdown input field'));
    await userEvent.type(screen.getByLabelText('Search An Item Here'), 'TEST1');
    expect(screen.queryByLabelText('TEST2')).not.toBeInTheDocument();
  });

  test('should handle when no option match searched input', async () => {
    render(<App options={['TEST1', 'TEST2']} isSearchable />);
    await userEvent.click(screen.getByLabelText('Dropdown input field'));
    await userEvent.type(screen.getByLabelText('Search An Item Here'), 'TEST1DUMMY');
    expect(screen.queryByLabelText('TEST1')).not.toBeInTheDocument();
    expect(screen.queryByLabelText('TEST2')).not.toBeInTheDocument();
    expect(screen.getByText('No results found')).toBeInTheDocument();
  });

  test('should be able to select multiple items', async () => {
    render(
      <App
        options={['TEST1', 'TEST2']}
        isLoading
        shouldAllowMultiple
        shouldCloseDropdownOnSelect={false}
      />,
    );
    await userEvent.click(screen.getByLabelText('Dropdown input field'));

    await userEvent.click(screen.getByLabelText('TEST1'));
    await userEvent.click(screen.getByLabelText('TEST2'));

    expect(screen.getByLabelText('Remove Selected Option -> TEST1')).toBeInTheDocument();
    expect(screen.getByLabelText('Remove Selected Option -> TEST2')).toBeInTheDocument();

    await userEvent.click(screen.getByLabelText('Remove Selected Option -> TEST2'));

    expect(screen.queryByLabelText('Remove Selected Option -> TEST2')).not.toBeInTheDocument();

    await userEvent.click(screen.getByLabelText('selected value'));

    expect(screen.getByLabelText('selected value').innerHTML).toEqual('TEST1');
  });

  test('should be able to select multiple items with an objects array', async () => {
    render(
      <App
        options={[
          {
            name: 'TEST1',
          },
          {
            name: 'TEST2',
          },
        ]}
        labelKey="name"
        isLoading
        shouldAllowMultiple
        shouldCloseDropdownOnSelect={false}
      >
        {(data) => data.map((e) => e.name)}
      </App>,
    );
    await userEvent.click(screen.getByLabelText('Dropdown input field'));

    await userEvent.click(screen.getByLabelText('TEST1'));
    await userEvent.click(screen.getByLabelText('TEST2'));

    await userEvent.click(screen.getByLabelText('selected value'));
    expect(screen.getByLabelText('selected value').innerHTML).toEqual('TEST1TEST2');
  });
});
