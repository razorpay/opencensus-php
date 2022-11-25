import ComponentRow from 'merchant/views/Settlements/v2/components/ComponentRow';
import { render, screen } from 'test-utils';
import { titleCase } from 'common/utils/rzp-utils';

const defaultProps = {
  breakupItem: {
    component: 'adjustment',
    count: 5,
    fee: 10000,
    tax: 20000,
    type: 'credit',
    amount: 400099999,
    settled_amount: 400069999,
  },
  newResponse: true,
};

jest.mock('common/ui/Amount', () => ({
  ...jest.requireActual('merchant/components/File/Upload'),
  __esModule: true,
  default: ({ value }) => <div data-testid="amount">{value}</div>,
}));

describe('Component Row', () => {
  const renderApp = (props) => render(<ComponentRow {...defaultProps} {...props} />);

  test('should render component row', () => {
    renderApp();
    expect(screen.getByText(titleCase(defaultProps.breakupItem.component))).toBeInTheDocument();
    expect(screen.getByText(titleCase(defaultProps.breakupItem.type))).toBeInTheDocument();
    expect(screen.getByText('Type')).toBeInTheDocument();
    const amountElements = screen.getAllByTestId('amount');
    expect(amountElements[0]).toHaveTextContent(defaultProps.breakupItem.amount);
    expect(screen.getByText('Fee')).toBeInTheDocument();
    expect(amountElements[1]).toHaveTextContent(defaultProps.breakupItem.fee);
    expect(screen.getByText('Tax')).toBeInTheDocument();
    expect(amountElements[2]).toHaveTextContent(defaultProps.breakupItem.tax);
    expect(screen.getByText('Settled Amount')).toBeInTheDocument();
    expect(amountElements[3]).toHaveTextContent(defaultProps.breakupItem.settled_amount);
    expect(screen.getByText('Count')).toBeInTheDocument();
    expect(screen.getByText(defaultProps.breakupItem.count)).toBeInTheDocument();
  });

  test('should not render fee,tax and settled amount if new response is false', () => {
    renderApp({ newResponse: false });
    expect(screen.queryByText('Fee')).not.toBeInTheDocument();
    expect(screen.queryByText('Tax')).not.toBeInTheDocument();
    expect(screen.queryByText('Settled Amount')).not.toBeInTheDocument();
    const amountElements = screen.getAllByTestId('amount');
    // as fee, tax and settled amount are not shown, the amount elements will be 1
    expect(amountElements).toHaveLength(1);
  });

  test('should render - when count is 0', () => {
    renderApp({ breakupItem: { ...defaultProps.breakupItem, count: 0 } });
    expect(screen.getByText('-')).toBeInTheDocument();
  });

  test('should render negative amount when type is debit', () => {
    renderApp({ breakupItem: { ...defaultProps.breakupItem, type: 'debit' } });
    const amountElements = screen.getAllByTestId('amount');
    expect(amountElements[3]).toHaveTextContent(-defaultProps.breakupItem.settled_amount);
  });

  test('should render class highlight-unreconciled when component is unreconciled', () => {
    renderApp({ breakupItem: { ...defaultProps.breakupItem, component: 'unreconciled' } });
    expect(screen.getByTestId(`settlementBreakup${defaultProps.breakupItem.type}`)).toHaveClass(
      'highlight-unreconciled-row',
    );
  });
});
