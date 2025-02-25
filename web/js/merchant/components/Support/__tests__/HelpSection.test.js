import { render } from 'test-utils';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';

import HelpSection from '../HelpSection';

jest.mock('merchant/views/TicketSupport/utils', () => ({
  CreateTicketEmitter: {
    on: jest.fn(),
    off: jest.fn(),
  },
  TicketSystemEmitter: {
    on: jest.fn(),
    off: jest.fn(),
    emit: jest.fn(),
  },
}));

jest.mock(
  'merchant/containers/Home/RTUX/MerchantOverview/MerchantOverviewData/Settlement/utils',
  () => ({
    isRiskFoh: jest.fn().mockReturnValue(false),
    isRiskDisabled: jest.fn().mockReturnValue(false),
  }),
);

const queryClient = new QueryClient();

describe.skip('HelpSection Component', () => {
  test('should not render Support component for non-Indian users', () => {
    const { getByTestId } = render(
      <QueryClientProvider client={queryClient}>
        <HelpSection
          history={{ location: { pathname: '/' } }}
          isHelpWidgetVisible={true}
          user={{
            isCountryIndia: false,
            live: true,
            merchant: {
              hold_funds: false,
            },
            tags: ['MS_risk_review_onhold'],
          }}
        />
      </QueryClientProvider>,
    );
    expect(getByTestId('component-wrapper')).toBeEmptyDOMElement();
  });
});
