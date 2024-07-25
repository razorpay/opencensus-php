import React from 'react';
import MerchantKYC from '../MerchantKYC';
import * as apiRequest from 'apps/pos/src/app/apis/SalesAssistedOnboarding';
import { render, screen, server, waitFor } from 'apps/pos/src/services/test/test-utils';
import { switchMerchantHandler } from 'apps/pos/src/services/mocks/handlers/switchMerchant';

jest.mock(
  'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/providers/useOnboardingContext',
  () => ({
    ...jest.requireActual(
      'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/providers/useOnboardingContext',
    ),
    __esModule: true,
    default: jest.fn(() => ({
      values: {
        merchantId: 'test_mid',
      },
    })),
  }),
);
const mockReplace = jest.fn();

describe('MerchantKYC', () => {
  beforeEach(() => {
    const location = {
      ...window.location,
      replace: mockReplace,
    };
    Object.defineProperty(window, 'location', {
      value: location,
    });
  });

  test('should render MerchantKYC on screen', async () => {
    render(<MerchantKYC />);
    expect(screen.getByLabelText('Loading KYC details')).toBeInTheDocument();
  });

  test('should call handleSwitchMerchant on mount and redirect to easy dashboard ', async () => {
    server.use(switchMerchantHandler('success'));
    const apiSpy = jest.spyOn(apiRequest, 'switchMerchant');
    render(<MerchantKYC />);
    expect(apiSpy).toHaveBeenCalledWith({ merchantId: 'test_mid' });
    await waitFor(() => {
      expect(mockReplace).toHaveBeenCalled();
    });
  });
});
