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

const variantOff = { variables: { result: 'off' } };

const mockAbExperiments = {
  checkout_editor_v2_preview: variantOff,
};

const SEARCH_INPUT_PLACEHOLDER = 'Search payment products, settings, and more';

jest.mock('common/splitz', () => ({
  useSplitzService: () => ({ abExperiments: mockAbExperiments }),
}));

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

  test('should fetch merchant website details if website compliance flow enabled', async () => {
    const initialState = getState({
      userData: {
        isWebsiteComplianceFlowEnabled: true,
      },
    });
    renderApp({ initialState });
    const searchInput = screen.getByPlaceholderText(SEARCH_INPUT_PLACEHOLDER);
    await userEvent.click(searchInput);
    await waitFor(() => {
      expect(websiteCompSpy).toHaveBeenCalledTimes(1);
    });
  });

  test('should fetch feature if not exists in feature state', async () => {
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
    const searchInput = screen.getByPlaceholderText(SEARCH_INPUT_PLACEHOLDER);
    await userEvent.click(searchInput);
    await waitFor(() => {
      expect(featureConfigSpy).toHaveBeenCalledTimes(1);
    });
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
    const searchInput = screen.getByRole('textbox');
    userEvent.click(searchInput);
    await waitFor(() => {
      expect(screen.getByRole('textbox')).toBeInTheDocument();
    });
  });

  test('Should fetch the enrollment status if `bundle_pricing` experiment is enabled', async () => {
    const initialState = getState({
      userData: {
        get isBundlePricingEnabled() {
          return true;
        },
      },
    });
    const fetchEnrollmentStatusSpy = jest.spyOn(fetchEnrollmentStatus, 'fetchEnrollmentStatus');
    renderApp({ initialState });
    const searchInput = screen.getByPlaceholderText(SEARCH_INPUT_PLACEHOLDER);
    await userEvent.click(searchInput);
    await waitFor(() => {
      expect(fetchEnrollmentStatusSpy).toHaveBeenCalledTimes(1);
    });
  });

  test('Should not fetch the enrollment status if `bundle_pricing` experiment is disabled', async () => {
    const initialState = getState({
      userData: {
        get isBundlePricingEnabled() {
          return false;
        },
      },
    });
    const fetchEnrollmentStatusSpy = jest.spyOn(fetchEnrollmentStatus, 'fetchEnrollmentStatus');
    renderApp({ initialState });
    const searchInput = screen.getByPlaceholderText(SEARCH_INPUT_PLACEHOLDER);
    await userEvent.click(searchInput);
    await waitFor(() => {
      expect(fetchEnrollmentStatusSpy).toHaveBeenCalledTimes(0);
    });
  });

  test('should render products on searching query in search bar and hide product on outside click', async () => {
    const initialState = getState();
    renderApp({ initialState });
    const search = screen.getByPlaceholderText(SEARCH_INPUT_PLACEHOLDER);
    await userEvent.type(search, 'account');
    expect(screen.getByText('Bank account details')).toBeInTheDocument();
    const outsideElement = screen.getByTestId('outside-div');
    await userEvent.click(outsideElement);
    expect(screen.queryByText('Bank account details')).not.toBeInTheDocument();
  });
});
