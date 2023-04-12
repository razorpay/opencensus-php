import React, { useState } from 'react';
import { render, screen, userEvent } from 'test-utils';
import { TextInput } from 'merchant_common/views/Reports/components';

describe('TextInput', () => {
  const App = (props) => {
    const [value, setValue] = useState('');
    return (
      <>
        <TextInput
          value={value}
          onChange={setValue}
          label="Test Text Input"
          placeHolder="Write fruit here"
          helpText="input help text"
          validate={props?.validate}
        />
        <div>
          Just a random text outside, You typed:
          <p>{value}</p>
        </div>
      </>
    );
  };

  test('should render component without error', async () => {
    render(<App />);
    expect(screen.getByText('Test Text Input')).toBeInTheDocument();
    expect(screen.getByPlaceholderText('Write fruit here')).toBeInTheDocument();
    expect(screen.getByText('input help text')).toBeInTheDocument();
    await userEvent.click(screen.getByText('Just a random text outside, You typed:'));
  });

  test('should update state when user types something in', async () => {
    render(<App validate={() => false} />);
    await userEvent.type(screen.getByLabelText('Text Input'), 'Hey just checking the fn...');
    expect(screen.getByText('Hey just checking the fn...')).toBeInTheDocument();
  });
});
