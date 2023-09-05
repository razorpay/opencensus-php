import { render } from 'test-utils';
import EntityAnalytics from 'merchant/views/Transactions/v2/Analytics/EntityAnalytics';

jest.mock('merchant/views/Transactions/v2/Analytics/EntityAnalytics/FailedPayments.tsx', () => ({
  __esModule: true,
  default: () => <div>Failed payments overview</div>,
}));

jest.mock('merchant/views/Transactions/v2/Analytics/EntityAnalytics/Refunds.tsx', () => ({
  __esModule: true,
  default: () => <div>Refunds overview</div>,
}));

export const renderApp = ({ type } = {}) => {
  render(<EntityAnalytics type={type} />);
};
