import { BannerType } from 'merchant/views/AccountAndSettings/PaymentMethods/typings';
import HPBanner from 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/components/InternationalCards/components/InternationalHPBanner/HPBanner';
import { render, screen } from 'test-utils';
import { testKnowMore } from './mocks/fixtures';

describe('HomePage Banner', () => {
  const renderApp = ({ type } = {}) => {
    return render(<HPBanner type={type} />, {
      showModal: true,
    });
  };

  beforeAll(() => {
    window.rzpQ = {
      component: jest.fn(),
      merchantActions: () => ({
        success: jest.fn(),
      }),
    };
  });

  test('should render homepage banner when type is approved', () => {
    renderApp({
      type: BannerType.APPROVED,
    });
    expect(screen.getByText('Activated')).toBeInTheDocument();
    expect(
      screen.getByText('Your request to activate international card payments was successful •'),
    ).toBeInTheDocument();
    testKnowMore();
  });

  test('should render banner when type is need clarification', () => {
    renderApp({
      type: BannerType.NEEDS_CLARIFICATION,
    });
    expect(screen.getByText('Needs Clarification')).toBeInTheDocument();
    expect(
      screen.getByText('We need a few more details for your international cards payment request •'),
    ).toBeInTheDocument();
    testKnowMore();
  });

  test('should render homepage banner when type is rejected', () => {
    renderApp({
      type: BannerType.REJECTED,
    });
    expect(screen.getByText('Rejected')).toBeInTheDocument();
    expect(
      screen.getByText('Your request to activate international card payments is rejected •'),
    ).toBeInTheDocument();
    testKnowMore();
  });

  test('should not render homepage banner when workflow type is closed', () => {
    renderApp();
    expect(screen.queryByText('Know More')).not.toBeInTheDocument();
  });
});
