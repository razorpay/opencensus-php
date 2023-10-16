import '@testing-library/jest-dom/extend-expect';
import * as showWhen from 'merchant/components/ShowWhen';
import * as config from 'merchant/reducers/config';
import * as websiteComp from 'merchant/reducers/websitecompliance';
import * as applicationsReducers from 'merchant/reducers/applications';
import { getState } from './mocks/fixtures';
import AccountAndSettingsHome from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/Home';
import React from 'react';
import { render, screen, server, waitFor } from 'test-utils';
import { fetchMerchantInstrumentHandler, fetchRequestedInstrumentHandler } from './mocks/handler';

jest.mock('common/splitz', () => ({
  useSplitzService: () => ({
    abExperiments: {},
  }),
}));

describe('AccountAndSettingsHomePage', () => {
  const fetchConnectedApplicationsSpy = jest.spyOn(
    applicationsReducers,
    'fetchConnectedApplications',
  );
  const websiteCompSpy = jest.spyOn(websiteComp, 'fetchMerchantWebsiteDetails');
  const featureConfigSpy = jest.spyOn(config, 'fetchFeatureByName');

  const renderApp = ({ props = {}, initialState }) => {
    render(<AccountAndSettingsHome {...props} />, {
      initialState,
    });
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

  test('should render profile section', () => {
    const initialState = getState({
      userData: {
        isWebsiteComplianceFlowEnabled: true,
      },
    });
    renderApp({ initialState });
    expect(screen.getByText('Merchant Profile')).toBeInTheDocument();
  });

  test('should call fetch merchant and requested instruments on mount and render section in account and products', async () => {
    server.use(fetchMerchantInstrumentHandler());
    server.use(fetchRequestedInstrumentHandler());
    const initialState = getState();
    jest.spyOn(showWhen, 'showWhenUtil').mockImplementation(({ additionalCondition }: any) => {
      additionalCondition(initialState?.session?.user);
      return true;
    });
    renderApp({ initialState });
    await waitFor(() => {
      expect(screen.getByText('Accounts and Product Sections')).toBeInTheDocument();
    });
    await waitFor(() => {
      expect(screen.getByText('8 sections found')).toBeInTheDocument();
    });
  });

  test('should fetch connected apps when user is allowed to view applications tab', async () => {
    const initialState = getState({
      userData: {
        isAllowedView: () => true,
      },
    });
    renderApp({ initialState });
    await waitFor(() => {
      expect(fetchConnectedApplicationsSpy).toHaveBeenCalled();
    });
  });

  test('should not fetch connected apps when user is not allowed to view applications tab', async () => {
    const initialState = getState({
      userData: {
        isAllowedView: () => false,
      },
    });
    renderApp({ initialState });
    await waitFor(() => {
      expect(fetchConnectedApplicationsSpy).not.toHaveBeenCalled();
    });
  });
});
