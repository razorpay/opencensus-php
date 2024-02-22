import React from 'react';

import RadioButtonGroup from 'common/components/RadioButtonGroup';
import { render, screen, userEvent } from 'test-utils';

describe('RadioButtonGroup', () => {
  const onChange = jest.fn();
  const defaultProps = {
    options: [
      { label: 'Option 1', value: 'option1' },
      { label: 'Option 2', value: 'option2' },
    ],
    onChange,
  };

  const renderApp = (props = {}) => {
    render(<RadioButtonGroup {...defaultProps} {...props} />);
  };

  beforeEach(() => {
    onChange.mockClear();
  });

  test('render radio button group', () => {
    renderApp();

    const radioButtonGroup = screen.getByTestId('radio-button-group');
    expect(radioButtonGroup).toBeVisible();
  });

  test('render radio buttons', () => {
    renderApp();

    const radioButtons = screen.getAllByRole('radio', { hidden: true }) as HTMLInputElement[];
    expect(radioButtons.length).toBe(2);
    expect(radioButtons[0].value).toEqual('option1');
    expect(radioButtons[1].value).toEqual('option2');
  });

  test('render radio button group with default first option selected', () => {
    renderApp();
    const checkedRadio = screen.getByRole('radio', {
      hidden: true,
      checked: true,
    }) as HTMLInputElement;
    expect(checkedRadio.value).toBe('option1');
  });

  test('renders with initial selection checked', () => {
    renderApp({ selectedOption: 'option2' });
    const checkedRadio = screen.getByRole('radio', {
      hidden: true,
      checked: true,
    }) as HTMLInputElement;
    expect(checkedRadio.value).toBe('option2');
  });

  test('handles option selection and triggers onChange', async () => {
    renderApp();

    const secondOption = screen.getByLabelText('Option 2');
    await userEvent.click(secondOption);

    expect(onChange).toHaveBeenCalledWith('option2');
    expect(secondOption).toBeChecked();
  });

  test('disable button group when disabled prop is true', () => {
    renderApp({ isDisabled: true });
    const radioButtons = screen.getAllByRole('radio', { hidden: true }) as HTMLInputElement[];
    expect(radioButtons.every((button) => button.disabled)).toBe(true);
  });

  test('disable button when disabled is true', () => {
    renderApp({
      options: [
        { label: 'Option 1', value: 'option1' },
        { label: 'Option 2', value: 'option2', disabled: true },
      ],
    });
    const secondOption = screen.getByLabelText('Option 2');
    expect(secondOption).toBeDisabled();
  });
});
