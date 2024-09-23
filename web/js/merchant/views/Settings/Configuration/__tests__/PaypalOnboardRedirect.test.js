import PaypalOnboardRedirect from 'merchant/views/Settings/Configuration/PaypalOnboardRedirect';
import { render } from 'test-utils';

jest.mock('common/splitz', () => ({
  useSplitzService: () => ({
    abExperiments: {
      paypal_onboard_redirect: { variables: { result: 'on' } },
    },
  }),
  withSplitzService: jest.fn(),
}));

describe('test for PaypalOnboardRedirect component', () => {
  it('should call postMessage & trigger event', () => {
    const windowParentSpy = jest.spyOn(window.parent, 'postMessage').mockImplementation(() => null);

    render(<PaypalOnboardRedirect />);

    expect(windowParentSpy).toHaveBeenCalledTimes(1);
  });
});
