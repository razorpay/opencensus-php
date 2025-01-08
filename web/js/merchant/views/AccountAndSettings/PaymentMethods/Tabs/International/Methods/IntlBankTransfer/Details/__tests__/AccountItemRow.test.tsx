import React from 'react';

import copyToClipboard from 'common/utils/copyToClipboard';
import { render, screen, userEvent, waitFor } from 'test-utils';

import AccountItemRow from '../AccountItemRow';

jest.mock('common/utils/copyToClipboard', () => jest.fn());

describe('Test AccountItemRow', () => {
  it('should render AccountItemRow', () => {
    render(<AccountItemRow label="Line 1" value="ABC" showDivider />);
    expect(screen.getByText('Line 1')).toBeInTheDocument();
    expect(screen.getByText('ABC')).toBeInTheDocument();
    expect(screen.getByLabelText('Copy detail')).toBeInTheDocument();
    expect(screen.getByTestId('row-divider')).toBeInTheDocument();
  });

  it('should copy value to clipboard', async () => {
    render(<AccountItemRow label="Line 1" value="ABC" showDivider />);
    await userEvent.click(screen.getByLabelText('Copy detail'));
    expect(copyToClipboard).toHaveBeenCalledWith('ABC');

    await waitFor(() => expect(screen.getByText('Copied')).toBeInTheDocument());
  });
});
