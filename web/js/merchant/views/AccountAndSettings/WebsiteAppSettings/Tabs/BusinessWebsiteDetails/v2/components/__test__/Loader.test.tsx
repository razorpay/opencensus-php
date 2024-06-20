import React from 'react';
import { render, screen, waitFor, userEvent } from 'test-utils';

import { WebsiteSubmitModalSteps } from '../../types';
import Loader from '../Loader';

const mockOnDismiss = jest.fn();
const mockOnClick = jest.fn();

const defaultProps = {
  isMobile: false,
  isOpen: true,
  onDismiss: mockOnDismiss,
  onClick: mockOnClick,
  variant: WebsiteSubmitModalSteps.MAIN_PAGE_SUBMIT_IN_PROGRESS,
};

const renderApp = (props = {}) => {
  const renderOutput = render(<Loader {...defaultProps} {...props} />);
  return renderOutput;
};

describe('Business website automation - Loader', () => {
  it('should show submit in progress modal', async () => {
    renderApp({
      variant: WebsiteSubmitModalSteps.MAIN_PAGE_SUBMIT_IN_PROGRESS,
    });
    await waitFor(() => {
      expect(
        screen.getByTestId(`loader-${WebsiteSubmitModalSteps.MAIN_PAGE_SUBMIT_IN_PROGRESS}`),
      ).toBeInTheDocument();
    });
  });

  it('should show submit success modal', async () => {
    renderApp({
      variant: WebsiteSubmitModalSteps.MAIN_PAGE_SUBMIT_SUCCESS,
    });
    expect(
      screen.getByTestId(`loader-${WebsiteSubmitModalSteps.MAIN_PAGE_SUBMIT_SUCCESS}`),
    ).toBeInTheDocument();
    expect(screen.getByText('Your website is submitted for verification')).toBeInTheDocument();
    const button = screen.getByRole('button', { name: 'Okay, got it' });
    expect(button).toBeInTheDocument();
    await userEvent.click(button);
    await waitFor(() => {
      expect(mockOnClick).toHaveBeenCalled();
    });
  });

  it('should show manual workflow raised modal', async () => {
    renderApp({
      variant: WebsiteSubmitModalSteps.MANUAL_WF_RAISED,
    });
    expect(
      screen.getByTestId(`loader-${WebsiteSubmitModalSteps.MANUAL_WF_RAISED}`),
    ).toBeInTheDocument();
    expect(screen.getByText('Your website is submitted for verification')).toBeInTheDocument();
    const button = screen.getByRole('button', { name: 'Okay, got it' });
    expect(button).toBeInTheDocument();
    await userEvent.click(button);
    await waitFor(() => {
      expect(mockOnClick).toHaveBeenCalled();
    });
  });

  it('should show website update success modal', async () => {
    renderApp({
      variant: WebsiteSubmitModalSteps.WEBSITE_UPDATE_SUCCESS,
    });
    expect(
      screen.getByTestId(`loader-${WebsiteSubmitModalSteps.WEBSITE_UPDATE_SUCCESS}`),
    ).toBeInTheDocument();
    expect(screen.getByText('Your website has been successfully verified')).toBeInTheDocument();
    const button = screen.getByRole('button', { name: 'Okay, got it' });
    expect(button).toBeInTheDocument();
    await userEvent.click(button);
    await waitFor(() => {
      expect(mockOnClick).toHaveBeenCalled();
    });
  });

  it('should show error modal when website liveness check fail', async () => {
    renderApp({
      variant: WebsiteSubmitModalSteps.MAIN_PAGE_ERROR,
    });
    expect(
      screen.getByTestId(`loader-${WebsiteSubmitModalSteps.MAIN_PAGE_ERROR}`),
    ).toBeInTheDocument();
    expect(screen.getByText('Your website isn’t ready for verification')).toBeInTheDocument();
    const button = screen.getByRole('button', { name: 'Okay, got it' });
    expect(button).toBeInTheDocument();
    await userEvent.click(button);
    await waitFor(() => {
      expect(mockOnClick).toHaveBeenCalled();
    });
  });

  it('should show submit success modal on mobile', async () => {
    renderApp({
      variant: WebsiteSubmitModalSteps.MAIN_PAGE_SUBMIT_SUCCESS,
      isMobile: true,
    });
    expect(
      screen.getByTestId(`loader-${WebsiteSubmitModalSteps.MAIN_PAGE_SUBMIT_SUCCESS}`),
    ).toBeInTheDocument();
    expect(screen.getByText('Your website is submitted for verification')).toBeInTheDocument();
    const button = screen.getByRole('button', { name: 'Okay, got it' });
    expect(button).toBeInTheDocument();
    await userEvent.click(button);
    await waitFor(() => {
      expect(mockOnClick).toHaveBeenCalled();
    });
  });
});
