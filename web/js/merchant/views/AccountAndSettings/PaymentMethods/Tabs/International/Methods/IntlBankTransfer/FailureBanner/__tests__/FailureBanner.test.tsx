import React from 'react';

import {
  renderWithQueryClient,
  server,
  queryClient,
} from 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/Methods/IntlBankTransfer/Details/__tests__/mocks/handlers';
import {
  getMockUser,
  vkycFailureBannerTests,
} from 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/Methods/IntlBankTransfer/FailureBanner/__tests__/mocks/fixtures';
import { failureBannerHandlers } from 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/Methods/IntlBankTransfer/FailureBanner/__tests__/mocks/handlers';
import { screen, waitFor } from 'test-utils';

import FailureBanner from '../index';

jest.mock('common/utils/copyToClipboard', () => jest.fn());

const render = () => {
  const mockUser = getMockUser();
  renderWithQueryClient(<FailureBanner />, {
    initialState: {
      session: {
        user: mockUser,
      },
    },
  });
};

describe('Test FailureBanner', () => {
  beforeAll(() => server.listen());
  afterEach(() => {
    server.resetHandlers();
    queryClient.clear();
  });
  afterAll(() => server.close());

  it('should show blacklisted merchant banner', async () => {
    const mockUser = getMockUser();
    renderWithQueryClient(<FailureBanner />, {
      initialState: {
        session: {
          user: {
            ...mockUser,
            merchant: {
              category: '5813',
            },
          },
        },
      },
    });

    await waitFor(() =>
      expect(
        screen.getByText(
          /Unfortunately, your request to activate international bank transfers is rejected/,
        ),
      ).toBeInTheDocument(),
    );
  });

  it('should show no promoter pan name banner', async () => {
    const mockUser = getMockUser();
    renderWithQueryClient(<FailureBanner />, {
      initialState: {
        session: {
          user: {
            ...mockUser,
            promoter_pan_name: null,
          },
        },
      },
    });

    await waitFor(() =>
      expect(
        screen.getByText(
          /Authorised Signatory PAN details are mandatory for activating virtual accounts/,
        ),
      ).toBeInTheDocument(),
    );
  });
  it('should not show the banner if purpose code is not added', async () => {
    server.use(
      failureBannerHandlers.successEddDetails(),
      failureBannerHandlers.successWithoutPurposeCode(),
    );

    render();

    await waitFor(() =>
      expect(
        screen.queryByText(/Update your IEC code to activate international bank transfers/),
      ).toBeNull(),
    );
  });

  test.each(vkycFailureBannerTests)('$test', async ({ input, output }) => {
    input();
    render();

    await waitFor(() => expect(screen.getByText(output())).toBeInTheDocument());
  });
});
