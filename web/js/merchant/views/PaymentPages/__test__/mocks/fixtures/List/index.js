import PaymentPagesContainer from 'merchant/views/PaymentPages/PaymentPages/List/index';
import { render } from 'test-utils';
import track from 'merchant/views/PaymentPages/PaymentPages/List/track';
import store from 'merchant/store';
const globalState = store.getState();

jest.mock('merchant/components/TestModeBanner', () => {
  return {
    __esModule: true,
    default: () => <div>Test Mode Banner</div>,
  };
});
jest.spyOn(track, 'load').mockImplementation(() => {});
jest.spyOn(track, 'searchCount').mockImplementation(() => {});
jest.spyOn(track, 'searchStatus').mockImplementation(() => {});
jest.spyOn(track, 'searchTitle').mockImplementation(() => {});
jest.spyOn(track, 'search').mockImplementation(() => {});
jest.spyOn(track, 'searchClear').mockImplementation(() => {});
jest.spyOn(track, 'createPaymentPage').mockImplementation(() => {});
jest.spyOn(track, 'paginate').mockImplementation(() => {});
jest.spyOn(track, 'takeTour').mockImplementation(() => {});
jest.spyOn(track, 'viewDoc').mockImplementation(() => {});
jest.spyOn(track, 'copyUrl').mockImplementation(() => {});
jest.spyOn(track, 'init').mockImplementation(() => {});

const renderApp = (initialState = {}, props = {}) =>
  render(<PaymentPagesContainer {...props} />, {
    initialState: {
      ...globalState,
      session: {
        ...globalState.session,
        user: {
          ...globalState.session.user,
          ...initialState.session.user,
          isAllowedEdit: jest.fn(),
          isOrgAllowedFunctionality: jest.fn(),
        },
      },
      wysiwyg: {
        ...globalState.wysiwyg,
        ...initialState.wysiwyg,
      },
    },
  });
export { renderApp };
