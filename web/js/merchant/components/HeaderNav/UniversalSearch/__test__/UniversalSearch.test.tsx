import '@testing-library/jest-dom/extend-expect';
import * as showWhen from 'merchant/components/ShowWhen';
import * as applicationsReducers from 'merchant/reducers/applications';
import * as fetchEnrollmentStatus from 'merchant/reducers/bundlePricing';
import * as config from 'merchant/reducers/config';
import * as websiteComp from 'merchant/reducers/websitecompliance';
import { getState } from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/__tests__/mocks/fixtures';
import {
  fetchMerchantInstrumentHandler,
  fetchRequestedInstrumentHandler,
} from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/__tests__/mocks/handler';
import React from 'react';
import { render, screen, server, userEvent, waitFor } from 'test-utils';
import UniversalSearch from 'merchant/components/HeaderNav/UniversalSearch';

describe('Universal Search', () => {
  const fetchConnectedApplicationsSpy = jest.spyOn(
    applicationsReducers,
    'fetchConnectedApplications',
  );
  const websiteCompSpy = jest.spyOn(websiteComp, 'fetchMerchantWebsiteDetails');
  const featureConfigSpy = jest.spyOn(config, 'fetchFeatureByName');

  const renderApp = ({ props = {}, initialState }) => {
    render(
      <div>
        {/* @ts-expect-error expect error */}
        <UniversalSearch {...props} />
        <div data-testid="outside-div" />
      </div>,
      {
        initialState,
      },
    );
  };

  beforeEach(() => {
    websiteCompSpy.mockClear();
    featureConfigSpy.mockClear();
    fetchConnectedApplicationsSpy.mockClear();
  });

  test('should fetch merchant website details if website compliance flow enabled', () => {
    const initialState = getState({
      userData: {
        isWebsiteComplianceFlowEnabled: true,
      },
    });
    renderApp({ initialState });
    expect(websiteCompSpy).toHaveBeenCalledTimes(1);
  });

  test('should fetch feature if not exists in feature state', () => {
    const initialState = getState({
      userData: {
        isWebsiteComplianceFlowEnabled: true,
      },
      config: {
        featureStatusConfig: {
          loading: false,
          data: {},
        },
      },
    });
    renderApp({ initialState });
    expect(featureConfigSpy).toHaveBeenCalledTimes(1);
  });

  test('should call fetch merchant and requested instruments on mount', async () => {
    server.use(fetchMerchantInstrumentHandler());
    server.use(fetchRequestedInstrumentHandler());
    const initialState = getState();
    jest.spyOn(showWhen, 'showWhenUtil').mockImplementation(({ additionalCondition }: any) => {
      additionalCondition(initialState?.session?.user);
      return true;
    });
    renderApp({ initialState });
    await waitFor(() => {
      expect(screen.getByRole('textbox')).toBeInTheDocument();
    });
  });

  test('Should fetch the enrollment status if `bundle_pricing` experiment is enabled', () => {
    const initialState = getState({
      userData: {
        get isBundlePricingEnabled() {
          return true;
        },
      },
    });
    const fetchEnrollmentStatusSpy = jest.spyOn(fetchEnrollmentStatus, 'fetchEnrollmentStatus');
    renderApp({ initialState });
    expect(fetchEnrollmentStatusSpy).toHaveBeenCalledTimes(1);
  });

  test('Should not fetch the enrollment status if `bundle_pricing` experiment is enabled', () => {
    const initialState = getState({
      userData: {
        get isBundlePricingEnabled() {
          return false;
        },
      },
    });
    const fetchEnrollmentStatusSpy = jest.spyOn(fetchEnrollmentStatus, 'fetchEnrollmentStatus');
    renderApp({ initialState });
    expect(fetchEnrollmentStatusSpy).toHaveBeenCalledTimes(0);
  });

  test('Should not fetch the enrollment status if `bundle_pricing` experiment is enabled', () => {
    const initialState = getState({
      userData: {
        get isBundlePricingEnabled() {
          return false;
        },
      },
    });
    const fetchEnrollmentStatusSpy = jest.spyOn(fetchEnrollmentStatus, 'fetchEnrollmentStatus');
    renderApp({ initialState });
    expect(fetchEnrollmentStatusSpy).toHaveBeenCalledTimes(0);
  });

  test('should render products on searching query in search bar and hide product on outside click', async () => {
    const initialState = getState();
    renderApp({ initialState });
    const search = screen.getByRole('textbox');
    await userEvent.type(search, 'account');
    expect(screen.getByText('Bank account details')).toBeInTheDocument();
    const outsideElement = screen.getByTestId('outside-div');
    await userEvent.click(outsideElement);
    expect(screen.queryByText('Bank account details')).not.toBeInTheDocument();
  });
});
