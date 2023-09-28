import { render } from 'test-utils';

import BatchList from 'merchant/containers/BatchNew/ListV2';

jest.mock('common/ui/Table/DataTable', () => () => <div>Data Table</div>);
jest.mock('merchant/components/BatchNew/ListFilter', () => () => <div>Batch List Filter</div>);

export const PAYMENT_LINKS = 'Payment Links';

export const defaultProps = {
  loading: false,
  gaEvents: { trackDownloadProcessedBatchReport: jest.fn(), trackGoToLinks: jest.fn() },
};

export function renderApp({ props = {}, userExtra = {} }) {
  return render(<BatchList {...props} />, {
    initialState: {
      session: {
        user: {
          findTag: () => true,
          isAllowedView: () => true,
          isOrgAllowedFunctionality: () => true,
          isAllowedMultiple: () => true,
          isPLBatchUploadEnabled: true,
          isSellerAppRole: true,
          isPaymentLinkBatchEnabledForSellerAppRole: false,
          ...userExtra,
        },
      },
    },
  });
}
