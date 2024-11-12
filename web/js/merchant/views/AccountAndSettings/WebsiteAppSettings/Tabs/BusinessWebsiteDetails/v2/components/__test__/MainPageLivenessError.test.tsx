import React from 'react';
import { render, screen, waitFor, userEvent } from 'test-utils';

import { MainPageFormData } from '../../types';
import MainPageLivenessError from '../MainPageLivenessError';

const mockOnDismiss = jest.fn();
const mockOnWebsiteChangeClick = jest.fn();

const defaultProps = {
  isMobile: false,
  isOpen: true,
  onDismiss: mockOnDismiss,
  onWebsiteChangeClick: mockOnWebsiteChangeClick,
  mainPageFormState: {
    url: {
      value: 'https://www.example.com',
    },
  } as MainPageFormData,
};

const renderApp = (props = {}) => {
  const renderOutput = render(<MainPageLivenessError {...defaultProps} {...props} />);
  return renderOutput;
};

describe('Business website automation - Main Page Liveness Error Modal', () => {
  it('should show submit in progress modal', async () => {
    renderApp();
    await waitFor(() => {
      expect(screen.getByText("We're having trouble verifying your website")).toBeInTheDocument();
    });
  });

  it('should call on website change click', async () => {
    renderApp();
    await userEvent.click(
      screen.getByRole('link', {
        name: 'Change',
      }),
    );
    expect(mockOnWebsiteChangeClick).toHaveBeenCalled();
  });
});
