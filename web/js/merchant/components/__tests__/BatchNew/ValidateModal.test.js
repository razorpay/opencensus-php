import cloneDeep from 'lodash/cloneDeep';

import BatchValidateModal from 'merchant/components/BatchNew/ValidateModal';
import store from 'merchant/store';
import { render, screen } from 'test-utils';

const storeData = store.getState();

const defaultProps = {
  status: 'exceed',
  fileUrl: false,
  notifyMsg: false,
  batchType: 'refund',
  user: {
    isOrgCurlec: true,
  },
};

jest.mock('merchant/components/File/Upload', () => ({
  ...jest.requireActual('merchant/components/File/Upload'),
  __esModule: true,
  default: () => {
    return <div>Upload Component</div>;
  },
}));

jest.mock('merchant/components/DocsLink', () => ({
  ...jest.requireActual('merchant/components/DocsLink'),
  __esModule: true,
  default: () => {
    return <div>Learn more</div>;
  },
}));

jest.mock('common/i18', () => {
  return {
    __esModule: true,
    withI18Service: (Component) => (props) =>
      (
        <Component
          {...props}
          i18={{ isConfigTagEnabled: (path) => path === 'refunds.instant_refunds' }}
        />
      ),
    useI18Service: () => ({
      isConfigTagEnabled: jest.fn(),
    }),
  };
});

window.cdnBaseUrl = 'https://razorpay.com';

const updateStore = (user) => {
  jest.spyOn(store, 'getState').mockImplementation(() => {
    const clonedStore = cloneDeep(storeData);
    clonedStore.session.user = {
      ...clonedStore.session.user,
      ...user,
    };
    return clonedStore;
  });
};

const renderApp = ({ props, state }) =>
  render(<BatchValidateModal {...props} />, { initialState: state });

describe('test for ValidateModal component', () => {
  test('should hide component if refunds.instant_refunds is enabled', () => {
    const props = { ...defaultProps };
    const state = updateStore({
      isOrgAllowedFunctionality: jest.fn,
    });
    const text = 'Learn more';
    renderApp({ props, state });
    expect(screen.queryByText(text)).not.toBeInTheDocument();
  });
});
