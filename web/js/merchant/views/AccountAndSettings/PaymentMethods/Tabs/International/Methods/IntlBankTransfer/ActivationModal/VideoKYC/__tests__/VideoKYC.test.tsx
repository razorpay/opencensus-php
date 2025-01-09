import React from 'react';

import copyToClipboard from 'common/utils/copyToClipboard';
import { render, screen, userEvent, waitFor } from 'test-utils';

import { setVideoKycState } from './mocks/states';
import VideoKYC from '../index';

jest.mock('common/utils/copyToClipboard', () => jest.fn());

describe('VideoKYC', () => {
  it('should render without breaking', () => {
    render(<VideoKYC />);

    expect(screen.getByText('Confirmation of authorised signatory')).toBeInTheDocument();
    expect(screen.getByRole('radio', { name: 'Yes, I am' })).toBeInTheDocument();
    expect(screen.getByRole('radio', { name: 'No, I am not' })).toBeInTheDocument();
  });

  it('should show video kyc steps after selecting yes option', async () => {
    render(<VideoKYC />);

    await userEvent.click(screen.getByRole('radio', { name: 'Yes, I am' }));

    await waitFor(() => {
      expect(screen.getByText('Start Video KYC')).toBeInTheDocument();
    });
  });

  it('should show video kyc web-link after selecting no option', async () => {
    setVideoKycState();

    render(<VideoKYC />);

    await userEvent.click(screen.getByRole('radio', { name: 'No, I am not John Doe' }));

    await waitFor(() => {
      expect(screen.getByText('Share link for Video KYC')).toBeInTheDocument();
    });

    expect(screen.getByRole('textbox', { name: 'Video KYC link' })).toHaveValue(
      'https://example.com',
    );

    await userEvent.click(screen.getByRole('button', { name: 'copy link' }));

    expect(copyToClipboard).toHaveBeenCalledWith('https://example.com');
  });
});
