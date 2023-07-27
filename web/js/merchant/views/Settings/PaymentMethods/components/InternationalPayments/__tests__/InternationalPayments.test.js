import { render, userEvent, screen } from 'test-utils';
import InternationalPayments from 'merchant/views/Settings/PaymentMethods/components/InternationalPayments/InternationalPayments';

describe('InternationalPayments', () => {
  const defaultProps = {
    config: {
      isKycComplete: true,
      isWebsiteAdded: true,
      questionnaireStatus: {
        new_flow: true,
        enablement_progress: 'complete',
        percentage_completion: 100,
      },
      currentStatusOnHeader: 'in_review',
      isRequestAccessAllowed: true,
      isInternationalBlackList: false,
    },
    onRequestAccessClick: jest.fn(),
  };

  const renderComponent = (props = {}) => {
    return render(<InternationalPayments {...defaultProps} {...props} />);
  };

  test('displays "International Payments" as the title', () => {
    renderComponent();
    expect(screen.getByText('International Payments')).toBeInTheDocument();
  });

  test('displays the status label based on currentStatusOnHeader prop', () => {
    renderComponent();
    const statusLabel = screen.getByText('Under Review');
    expect(statusLabel).toBeInTheDocument();
  });

  test('displays the activation status component', () => {
    renderComponent();
    const activationStatus = screen.getByText(
      /Your request to enable international payments has been received by us and will be processed in 3-5 business days/,
    );
    expect(activationStatus).toBeInTheDocument();
  });

  test('disables the button when isKycComplete is false', async () => {
    const props = {
      config: {
        ...defaultProps.config,
        isKycComplete: false,
      },
    };
    renderComponent(props);
    const button = screen.getByText('Request');
    await userEvent.click(button);
    expect(defaultProps.onRequestAccessClick).not.toHaveBeenCalled();
  });

  test('calls onRequestAccessClick when the button is clicked', async () => {
    renderComponent();
    const button = screen.getByText('Request');
    await userEvent.click(button);
    expect(defaultProps.onRequestAccessClick).toHaveBeenCalled();
  });

  test('displays the "International payments is not supported" message when isInternationalBlackList is true', () => {
    const props = {
      config: {
        ...defaultProps.config,
        isInternationalBlackList: true,
      },
    };
    renderComponent(props);
    const message = screen.getByText(
      'International payments is not supported for your business type',
    );
    expect(message).toBeInTheDocument();
  });

  test('displays the "Please update your website" message when isWebsiteAdded is false', () => {
    const props = {
      config: {
        ...defaultProps.config,
        isWebsiteAdded: false,
      },
    };
    renderComponent(props);
    const message = screen.getByText(
      'Please update your website to request for international payments',
    );
    expect(message).toBeInTheDocument();
  });

  test('displays Request button when isRequestAccessAllowed', () => {
    const props = {
      config: {
        ...defaultProps.config,
        isRequestAccessAllowed: true,
      },
    };
    renderComponent(props);
    const button = screen.getByText('Request');
    expect(button).toBeInTheDocument();
  });

  test('displays Edit Draft when questionnaireStatus.new_flow is true', () => {
    const props = {
      config: {
        ...defaultProps.config,
        questionnaireStatus: {
          new_flow: true,
          enablement_progress: 'in_progress',
          percentage_completion: 30,
        },
      },
    };
    renderComponent(props);
    const button = screen.getByRole('button', { text: /Edit draft/ });
    expect(button).toBeInTheDocument();
  });
});
