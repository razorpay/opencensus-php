import { render } from 'test-utils';

import Transactions from 'merchant/views/Transactions';

jest.mock('merchant/views/Transactions/v1', () => () => <div>TransactionsV1</div>);

jest.mock('merchant/views/Transactions/v2', () => () => <div>TransactionsV2</div>);

jest.mock('common/splitz', () => ({
  useSplitzService: jest.fn().mockReturnValue({ some: 'splitz-data' }),
}));

jest.mock('merchant/views/Transactions/v2/common/utils', () => ({
  isTransactionsV2Enabled: jest.fn().mockReturnValue(true),
}));

export const renderApp = () => {
  return render(<Transactions />);
};
