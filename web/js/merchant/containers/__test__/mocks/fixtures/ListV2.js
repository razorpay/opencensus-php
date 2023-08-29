import { render } from 'test-utils';

import BatchList from 'merchant/containers/BatchNew/ListV2';

jest.mock('common/ui/Table/DataTable', () => () => <div>Data Table</div>);
jest.mock('merchant/components/BatchNew/ListFilter', () => () => <div>Batch List Filter</div>);
jest.mock('merchant/components/ShowWhen', () => ({ children }) => <div>{children}</div>);

export const PAYMENT_LINKS = 'Payment Links';
export const LOCATION = {
  pathname: '/paymentpages/batchuploads/pl_MREbR18TEYXeqv/test',
  search: '',
  hash: '',
  key: 'ou239n',
};

export const defaultProps = {
  loading: false,
  gaEvents: { trackDownloadProcessedBatchReport: jest.fn(), trackGoToLinks: jest.fn() },
  location: LOCATION,
  user: {
    isAllowedView: () => true,
    isPLBatchUploadEnabled: true,
    isSellerAppRole: true,
    isPaymentLinkBatchEnabledForSellerAppRole: false,
  },
};

export function renderApp({ props = {} }) {
  return render(<BatchList {...props} />);
}
