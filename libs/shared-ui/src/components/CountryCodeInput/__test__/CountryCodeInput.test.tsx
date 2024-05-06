import React from 'react';
import CountryCodeInput from '../CountryCodeInput';
import { userEvent, render, screen } from '@dashboard/shared-ui/services/test/test-utils';

const phoneNumber = '9999999999';

describe('CountryCodeInput component', () => {
  test('renders without error', () => {
    const App = (): JSX.Element => <CountryCodeInput />;
    render(<App />, {});
    expect(screen.getByText('+91')).toBeInTheDocument();
  });

  test('render with default props', () => {
    const App = (): JSX.Element => <CountryCodeInput dialCode="+44" value={phoneNumber} />;
    render(<App />, {});
    const dialCodeValue = screen.getByTestId('dialCodeValue');
    expect(dialCodeValue.innerHTML).toBe('+44');
    // eslint-disable-next-line @typescript-eslint/no-unnecessary-type-assertion
    const contactInput = screen.getByTestId('contactInput') as HTMLInputElement;
    expect(contactInput.value).toBe(phoneNumber);
  });
  test('test action props', async () => {
    const onDialCodeChange = jest.fn();
    const onChange = jest.fn();
    const onContactChange = jest.fn();
    const App = (): JSX.Element => (
      <CountryCodeInput
        onChange={onChange}
        onDialCodeChange={onDialCodeChange}
        onContactChange={onContactChange}
        dialCode="+44"
      />
    );
    render(<App />, {});
    const dialCodeValue = screen.getByTestId('dialCodeValue');
    expect(dialCodeValue.innerHTML).toBe('+44');
    const dialCodeSelector = screen.getByTestId('dialCodeSelector');
    await userEvent.click(dialCodeSelector);
    const dropdownItems = screen.getByTestId('dropdownItems');
    expect(dropdownItems).toBeInTheDocument();
    (document.querySelector('.flag.in') as HTMLSpanElement)?.click();
    expect(dialCodeValue.innerHTML).toBe('+91');
    expect(onChange).toBeCalledTimes(1);
    expect(onDialCodeChange).toBeCalledTimes(1);
    expect(onContactChange).toBeCalledTimes(0);
    const contactInput = screen.getByTestId('contactInput');
    await userEvent.type(contactInput, phoneNumber);
    expect(onContactChange).toBeCalledTimes(10);
    expect(onContactChange).toBeCalledWith(phoneNumber);
    expect(onChange).toBeCalledTimes(11);
    expect(onChange).toBeCalledWith({
      value: phoneNumber,
      dialCode: '+91',
    });
  });
});
