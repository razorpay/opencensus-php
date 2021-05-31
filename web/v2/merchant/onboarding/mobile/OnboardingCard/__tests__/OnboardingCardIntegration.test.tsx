import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import OnboardingCard from '../index';
import { render, waitForElementToBeRemoved, screen, fireEvent, act, waitFor } from 'test-utils';

const waitForOnboardingPageLoadingToFinish = () =>
  waitForElementToBeRemoved(() => [...screen.queryAllByRole('shimmer')], { timeout: 4000 });

const waitForLoadingToFinish = () =>
  waitForElementToBeRemoved(() => [...screen.queryAllByRole('loader')], { timeout: 4000 });

test.skip('flow for a private whiteList merchant- OnboardingCard', async () => {
  jest.setTimeout(30000);
  render(<OnboardingCard />, {});
  await waitForOnboardingPageLoadingToFinish();
  expect(screen.getByText('Activate Your Account')).toBeInTheDocument();
  expect(screen.getAllByTestId('ds-text-input').length).toBe(2);
  const businessTypeSelect = screen.getAllByTestId('ds-text-input')[0];
  fireEvent.click(businessTypeSelect);
  expect(screen.getByText('Private Limited')).toBeInTheDocument();
  fireEvent.click(screen.getByText('Private Limited'));
  expect(screen.queryByText('LLP')).not.toBeInTheDocument();
  const businessCategorySelect = screen.getAllByTestId('ds-text-input')[1];
  fireEvent.click(businessCategorySelect);
  await waitForLoadingToFinish();
  expect(screen.getByText('ecommerce')).toBeInTheDocument();
  expect(screen.getByPlaceholderText('Search Business Category')).toBeInTheDocument();
  fireEvent.change(screen.getByPlaceholderText('Search Business Category'), {
    target: { value: 'Comp' },
  });
  expect(
    screen.getByText('Computers, Computer Peripheral Equipment, Software'),
  ).toBeInTheDocument();
  act(() => {
    fireEvent.click(screen.getByText('Computers, Computer Peripheral Equipment, Software'));
  });

  await waitFor(() => {
    expect(screen.getByText('Start Activation')).toBeInTheDocument();
  });
});
test.skip('flow for a unregistered blacklisted merchant', async () => {
  jest.setTimeout(30000);
  render(<OnboardingCard />, {});
  await waitForOnboardingPageLoadingToFinish();
  expect(screen.getByText('Activate Your Account')).toBeInTheDocument();
  expect(screen.getAllByTestId('ds-text-input').length).toBe(2);
  const businessTypeSelect = screen.getAllByTestId('ds-text-input')[0];
  fireEvent.click(businessTypeSelect);
  expect(screen.getByText('Not Registered')).toBeInTheDocument();
  fireEvent.click(screen.getByText('Not Registered'));
  expect(screen.queryByText('LLP')).not.toBeInTheDocument();
  const businessCategorySelect = screen.getAllByTestId('ds-text-input')[1];
  fireEvent.click(businessCategorySelect);
  await waitForLoadingToFinish();
  expect(screen.getByText('ecommerce')).toBeInTheDocument();
  expect(screen.getByText('Computer Software Stores')).toBeInTheDocument();
  fireEvent.click(screen.getByText('Computer Software Stores'));
  waitFor(() =>
    expect(
      screen.getByText('We do not have the support for your business category selected as of now.'),
    ).toBeInTheDocument(),
  );
});
