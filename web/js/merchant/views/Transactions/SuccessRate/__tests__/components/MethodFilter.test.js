import React from 'react';
import { render, screen } from 'test-utils';
import MethodFilter from 'merchant/views/Transactions/SuccessRate/components/MethodFilter';
import { DEFAULT_PROPS } from 'merchant/views/Transactions/SuccessRate/__tests__/mocks/fixtures';
jest.mock('merchant/views/Transactions/SuccessRate/components/MethodType', () => () => (
  <div className="panel-actions" data-testid="method-types">
    <label>Card Type:</label>
    <div className="panel-action-item rzp-btn-group btn-group">
      <button className="btn-default btn active">Credit</button>
      <button className="btn-default btn">Debit</button>
      <button className="btn-default btn">Prepaid</button>
    </div>
  </div>
));

const defaultProps = DEFAULT_PROPS.MethodFilter;
describe('MethodFilter Optimiser tests', () => {
  const renderComponent = (props) => render(<MethodFilter {...props} />);

  afterEach(() => {
    jest.resetAllMocks();
  });

  test('should not render the component when filtersList is empty', () => {
    renderComponent({ ...defaultProps, filtersList: [] });
    expect(screen.queryByTestId('method-types')).toBeNull();
  });

  test('should not render the CardTypes component', () => {
    renderComponent(defaultProps);
    expect(screen.queryByTestId('method-types')).toBeNull();
    expect(screen.queryByTestId('card-types-button')).toBeNull();
  });

  test('should render the CardTypes component', () => {
    renderComponent({
      ...defaultProps,
      isOptimizerEnabled: false,
      user: { isOptimizerEnabled: false },
    });
    expect(screen.queryByTestId('method-types')).toBeInTheDocument();
    expect(screen.queryByText('Card Type:')).toBeInTheDocument();

    const buttons = screen.getAllByRole('button');
    expect(buttons).toHaveLength(3);

    const buttonLabels = ['Credit', 'Debit', 'Prepaid'];
    buttons.forEach((button, index) => {
      expect(button).toBeInTheDocument();
      expect(button).toHaveClass('btn-default');
      if (index === 0) {
        expect(button).toHaveClass('active');
      } else {
        expect(button).not.toHaveClass('active');
      }
      expect(button).toHaveTextContent(buttonLabels[index]);
    });
  });

  test('should render the Grouping Dropdown Filter component', () => {
    renderComponent({
      ...defaultProps,
      isOptimizerEnabled: false,
      user: { isOptimizerEnabled: false },
    });
    expect(screen.getByTestId('method-types')).toBeInTheDocument();
    expect(screen.getByText('Filter:')).toBeInTheDocument();
    expect(screen.getByTestId('group-filters-dropdown')).toBeInTheDocument();
    expect(screen.getByText('Card Networks')).toBeInTheDocument();
  });
});
