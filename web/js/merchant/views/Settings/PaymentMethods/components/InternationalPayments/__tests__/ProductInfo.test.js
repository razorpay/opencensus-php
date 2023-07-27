import { render, screen, userEvent } from 'test-utils';
import ProductInfo from 'merchant/views/Settings/PaymentMethods/components/InternationalPayments/ProductInfo';

describe('test ProductInfo', () => {
  const defaultProps = {
    title: 'Product Title',
    status: 'approved',
    product: 'pg',
    transactionSize: 100,
    settlementCycle: 7,
    showStatusLabel: true,
    showRequestAccessBtn: false,
    questionnaireStatus: null,
    onRequestAccessClick: jest.fn(),
  };

  const renderComponent = (props = {}) => {
    return render(<ProductInfo {...defaultProps} {...props} />);
  };

  test('renders title correctly', () => {
    renderComponent();
    const titleElement = screen.getByText('Product Title');
    expect(titleElement).toBeInTheDocument();
  });

  test('renders rejected description correctly', () => {
    const props = {
      status: 'rejected',
    };
    renderComponent(props);
    const descriptionElement = screen.getByText(
      'Currently we do not support international payments for these products. Please reach out to support for any queries',
    );
    expect(descriptionElement).toBeInTheDocument();
  });

  test('renders in_review description correctly', () => {
    const props = {
      status: 'in_review',
    };
    renderComponent(props);
    const descriptionElement = screen.getByText(
      'Request has been submitted. We are verifying your request. This would take roughly 3-5 days.',
    );
    expect(descriptionElement).toBeInTheDocument();
  });

  test('renders no_action_received description correctly', () => {
    const props = {
      status: 'no_action_received',
    };
    renderComponent(props);
    const descriptionElement = screen.getByText(
      'Raise a request to activate international card payments on payment gateway',
    );
    expect(descriptionElement).toBeInTheDocument();
  });

  test('renders approved description correctly', () => {
    const props = {
      status: 'approved',
    };
    renderComponent(props);
    const transactionSizeElement = screen.getByText('Transaction Size Enabled :');
    const settlementCycleElement = screen.getByText('Settlement Cycle :');
    expect(transactionSizeElement).toBeInTheDocument();
    expect(settlementCycleElement).toBeInTheDocument();
  });

  test('does not render anything for unknown status', () => {
    const props = {
      status: 'unknown',
    };
    renderComponent(props);
    const productInfoElement = screen.queryByText('Product Title');
    expect(productInfoElement).not.toBeInTheDocument();
  });

  test('renders request access button', async () => {
    const props = {
      showRequestAccessBtn: true,
    };

    renderComponent(props);
    const requestAccessButton = screen.getByRole('button', { text: /Request Access/ });
    await userEvent.click(requestAccessButton);

    expect(defaultProps.onRequestAccessClick).toHaveBeenCalled();
  });

  test('renders activation progress', async () => {
    const props = {
      showRequestAccessBtn: true,
      questionnaireStatus: {
        new_flow: true,
        enablement_progress: 'in_progress',
        percentage_completion: 80,
      },
    };

    renderComponent(props);

    const requestAccessButton = screen.getByRole('button', { text: /Edit draft/ });
    await userEvent.click(requestAccessButton);

    expect(defaultProps.onRequestAccessClick).toHaveBeenCalled();
  });
});
