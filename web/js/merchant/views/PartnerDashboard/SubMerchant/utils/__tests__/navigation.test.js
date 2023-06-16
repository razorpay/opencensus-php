import { openKYCFormUtil } from 'merchant/views/PartnerDashboard/SubMerchant/utils/navigation';
import { createMemoryHistory } from 'history';
import { server } from 'test-utils';
import { isEasyEnabledHandler } from './mocks/handlers';

let history;

const submerchantDefaults = {
  id: 'acc_Ao6iPyuWSzc3dr',
  user: {
    id: 'Ao6iPyuWSzc3dr',
  },
};

describe('Navigation Utils', () => {
  beforeAll(() => {
    window.open = jest.fn();
    window.EASY_ONBOARDING_URL = 'https://easy.razorpay.com';
    history = createMemoryHistory();
    history.push = jest.fn();
  });

  test('should open a new tab to easy onboarding if routing via easy', async () => {
    const is_mweb = false;
    const showNotification = () => {};
    server.use(isEasyEnabledHandler(true));
    await openKYCFormUtil(is_mweb, history, submerchantDefaults, showNotification);
    expect(window.open).toHaveBeenCalledWith(
      'https://easy.razorpay.com/onboarding?account_id=acc_Ao6iPyuWSzc3dr',
      'submerchant_onboarding_via_easy',
    );
  });

  test('should route to mweb activation route', async () => {
    const is_mweb = true;
    const showNotification = () => {};
    server.use(isEasyEnabledHandler(false));
    await openKYCFormUtil(is_mweb, history, submerchantDefaults, showNotification);
    expect(history.push).toHaveBeenCalledWith(
      `/partners/submerchants/onboarding/acc_Ao6iPyuWSzc3dr/steps`,
    );
  });

  test('should route to desktop activation route', async () => {
    const is_mweb = false;
    server.use(isEasyEnabledHandler(false));
    await openKYCFormUtil(is_mweb, history, submerchantDefaults);
    expect(history.push).toHaveBeenCalledWith(
      `/partners/submerchants/acc_Ao6iPyuWSzc3dr/activation`,
    );
  });
});
