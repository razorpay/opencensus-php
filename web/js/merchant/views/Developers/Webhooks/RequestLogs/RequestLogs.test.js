import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import { render, screen, userEvent } from 'test-utils';
import RequestLogs from './RequestLogs';

describe('Developer console Api request logs', () => {
  test('should change value of select box properly', async () => {
    render(<RequestLogs selectedFilters={{ duration: 1 }} />);
    await userEvent.selectOptions(screen.getByTestId('select'), '2xx');
    const options = screen.getAllByTestId('select-option');

    expect(options[0].selected).toBeTruthy();
    expect(options[1].selected).toBeFalsy();
    expect(options[2].selected).toBeFalsy();
    expect(options[3].selected).toBeFalsy();
    expect(options[4].selected).toBeFalsy();
  });
});
