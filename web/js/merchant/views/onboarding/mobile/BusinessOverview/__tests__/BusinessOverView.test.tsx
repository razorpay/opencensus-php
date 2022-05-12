/* eslint-disable @typescript-eslint/no-unused-vars */
import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import { fireEvent, render, screen, waitForElementToBeRemoved, waitFor } from 'test-utils';
import BusinessOverview from '../index';
import useActivation from '../../hooks/useActivation';
import * as ActivationDB from '../../services/data/ActivationDB';
afterEach(() => {
  ActivationDB.reset();
});
const waitForLoadingToFinish = () => waitForElementToBeRemoved(screen.queryByText('Loading...'));
const App: React.FC = () => {
  const { status } = useActivation();
  if (status === 'loading') return <div>Loading...</div>;
  return <BusinessOverview />;
};
test('renders all the input fields of the form correctly', async () => {
  ActivationDB.update({
    merchant_business_detail: {
      website_details: {
        live_website_or_app: 1,
      },
    },
  });
  render(<App />, {});
  await waitForLoadingToFinish();
  expect(screen.getByText('About Your Business')).toBeInTheDocument();

  const [businessTypeInput, businessCategorySelect, AovField]: any = screen.getAllByTestId(
    'ds-text-input',
  );

  const businessModal = screen.getByTestId('ds-text-area');
  fireEvent.click(businessTypeInput);
  await waitFor(() => {
    expect(screen.getByText('Private Limited')).toBeInTheDocument();
    expect(screen.getByText('Partnership')).toBeInTheDocument();
  });

  fireEvent.click(businessCategorySelect);
  fireEvent.change(businessCategorySelect, { target: { value: 'ecomerce' } });

  fireEvent.click(screen.getByText('Private Limited'));
  expect(businessTypeInput.value).toBe('Private Limited');

  fireEvent.change(businessModal, {
    target: {
      value:
        "Unregistered business type is for freelancers or small businesses who have not yet registered as a company. Don't choose this option if your business is already registered. Business type cannot be changed once submitted.",
    },
  });
  fireEvent.blur(businessModal);

  fireEvent.click(AovField);
  fireEvent.change(AovField, { target: { value: { value: '₹ 1 - ₹ 150' } } });
  fireEvent.blur(AovField);

  expect(screen.getByText('Payment Channels')).toBeInTheDocument();
  expect(
    screen.getByText('This allows us to recommend a suitable product for your business'),
  ).toBeInTheDocument();
  expect(screen.getByText('Store/ In-person')).toBeInTheDocument();
  expect(screen.getByText('Social Media (e.g. WhatsApp)')).toBeInTheDocument();
  expect(screen.getByText('Live Website/App')).toBeInTheDocument();

  const liveWebsiteOrAppCheckbox = screen.getByText('Live Website/App');
  fireEvent.click(liveWebsiteOrAppCheckbox);

  expect(screen.getByText('Accept payments on website')).toBeInTheDocument();
  const websiteCheckbox = screen.getByText('Accept payments on website');
  fireEvent.click(websiteCheckbox);
  expect(screen.getByText('Website URL')).toBeInTheDocument();
  expect(
    screen.getByText('Check the pages/section required on the app by clicking on the info icon'),
  ).toBeInTheDocument();
  const website = screen.getAllByTestId('ds-text-input')[3];
  fireEvent.change(website, { target: { value: 'www.google.com' } });
  fireEvent.blur(website);

  expect(screen.getByText('Accept payments on app')).toBeInTheDocument();
  const appCheckbox = screen.getByText('Accept payments on app');
  fireEvent.click(appCheckbox);
  expect(screen.getByText('App URL')).toBeInTheDocument();
  await waitFor(() => fireEvent.change(appCheckbox, { target: { checked: false } }));
  await waitFor(() => fireEvent.change(websiteCheckbox, { target: { checked: false } }));
  await waitFor(() => fireEvent.change(liveWebsiteOrAppCheckbox, { target: { checked: false } }));
});
