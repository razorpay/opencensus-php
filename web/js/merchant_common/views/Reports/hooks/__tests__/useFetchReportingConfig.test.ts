import { renderHook, waitFor } from 'test-utils';

import { SessionReducerState } from 'common/typings';
import { isJKOfflineMerchant } from 'merchant/components/Sidebar/helpers';
import { getConfigs } from 'merchant_common/views/Reports/api/overview';
import { mockConfigs } from 'merchant_common/views/Reports/redux/__test__/fixtures/configs.fixtures';

import { getReportsDashboardConfig } from 'merchant_common/views/Reports/configs/refDashboard.config';
import { useFetchReportingConfig } from 'merchant_common/views/Reports/hooks/useFetchReportingConfig';

jest.mock('merchant/components/Sidebar/helpers', () => ({
  isJKOfflineMerchant: jest.fn(),
}));

jest.mock('merchant_common/views/Reports/api/overview', () => ({
  getConfigs: jest.fn(),
}));

const MOCK_PROPS = {
  headers: 'mock-headers',
  fetchReportsConfigsSuccess: jest.fn(),
  fetchReportsConfigsFailed: jest.fn(),
  handleOverviewLoading: jest.fn(),
  dashboardType: 'merchant',
};

describe('Validate: useFetchReportingConfig', () => {
  const setup = (args) => {
    return renderHook(() =>
      useFetchReportingConfig({
        ...args,
      }),
    );
  };

  test('Should pass appropriate configs to the success handler', async () => {
    (getConfigs as jest.Mock).mockResolvedValue({
      data: {
        items: mockConfigs,
      },
    });
    const { parseConfigs } = getReportsDashboardConfig('merchant', {
      user: {
        findTag: () => true,
        isPaymentPageFileUploadEnabled: () => true,
      },
    } as unknown as SessionReducerState);

    setup({
      ...MOCK_PROPS,
      parseConfigs,
    });
    expect(MOCK_PROPS.handleOverviewLoading).toHaveBeenCalled();
    await waitFor(() => {
      expect(MOCK_PROPS.fetchReportsConfigsSuccess).toHaveBeenCalled();
      const callArgs = MOCK_PROPS.fetchReportsConfigsSuccess.mock.calls[0][0];
      expect(callArgs.configs).toHaveLength(mockConfigs.length);
    });
  });
  test('Should pass appropriate configs to the success handler if Org is J&K', async () => {
    (isJKOfflineMerchant as jest.Mock).mockReturnValue(true);
    (getConfigs as jest.Mock).mockResolvedValue({
      data: {
        items: mockConfigs,
      },
    });
    const { parseConfigs } = getReportsDashboardConfig('merchant', {
      user: {
        findTag: () => true,
      },
    } as unknown as SessionReducerState);

    setup({
      ...MOCK_PROPS,
      parseConfigs,
    });
    expect(MOCK_PROPS.handleOverviewLoading).toHaveBeenCalled();
    await waitFor(() => {
      expect(MOCK_PROPS.fetchReportsConfigsSuccess).toHaveBeenCalledWith(
        expect.objectContaining({
          configs: expect.arrayContaining([
            expect.objectContaining({
              type: 'payments',
              name: 'Payments',
            }),
          ]),
        }),
      );
      const callArgs = MOCK_PROPS.fetchReportsConfigsSuccess.mock.calls[0][0];
      expect(callArgs.configs).toHaveLength(1);
    });
  });
});
