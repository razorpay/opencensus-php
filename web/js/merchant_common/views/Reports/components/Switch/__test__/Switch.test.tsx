import React, { useState } from 'react';
import { render, screen, userEvent } from 'test-utils';
import { Switch } from 'merchant_common/views/Reports/components';

describe('Switch', () => {
  const App = () => {
    const [isEnabled, setIsEnabled] = useState(false);
    return <Switch value={isEnabled} onChange={setIsEnabled} label="Testing" />;
  };

  test('should render component without error', () => {
    render(<App />);
    expect(screen.getByText('Testing')).toBeInTheDocument();
    expect(screen.getByLabelText('Testing Switch')).toBeInTheDocument();
    expect(screen.getByLabelText('Testing Switch').children[0]).toHaveAttribute('value', 'false');
  });

  test('should update state when switch is toggled', async () => {
    render(<App />);
    await userEvent.click(screen.getByLabelText('Testing Switch'));
    expect(screen.getByLabelText('Testing Switch').children[0]).toHaveAttribute('value', 'true');
  });
});
