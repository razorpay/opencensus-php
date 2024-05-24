import { render, screen } from 'test-utils';

import DefaultRefundSpeed from 'merchant/views/Settings/Configuration/DefaultRefundSpeed';

const mockedIsConfigTagEnabled = jest.fn();

jest.mock('common/i18', () => ({
  __esModule: true,
  withI18Service: (Component) => (props) =>
    <Component {...props} i18={{ isConfigTagEnabled: mockedIsConfigTagEnabled }} />,
  useI18Service: () => ({
    isConfigTagEnabled: mockedIsConfigTagEnabled,
  }),
}));

describe('test for DefaultRefundSpeed component', () => {
  it('should hide "Instant Refund" if "isConfigTagEnabled" returns false', () => {
    mockedIsConfigTagEnabled.mockReturnValue(false);

    const initialState = {
      session: {
        user: { id: 'user123' },
        org: { features: ['enable_refunds'] },
      },
      config: {
        refund_pricing: {},
        features: [],
        config: { default_refund_speed: 'normal' },
        lateAuthConfig: { data: { items: [] }, error: null },
      },
    };

    render(<DefaultRefundSpeed />, { initialState });

    expect(screen.queryByText('Instant Refund')).not.toBeInTheDocument();
  });
});
