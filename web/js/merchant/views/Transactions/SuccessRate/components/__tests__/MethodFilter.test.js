import React from 'react';
import { render, screen } from 'test-utils';
import MethodFilter from 'merchant/views/Transactions/SuccessRate/components/MethodFilter';
import { SR_FILTERS } from 'merchant/views/Transactions/SuccessRate/constants';

// mock CardTypes component
jest.mock('../CardTypes', () => () => (
  <div className="panel-actions">
    <div className="panel-action-item rzp-btn-group btn-group">
      <button className="btn-default btn active">Credit</button>
      <button className="btn-default btn">Debit</button>
      <button className="btn-default btn">Prepaid</button>
    </div>
  </div>
));

describe('MethodFilter', () => {
  afterEach(() => {
    jest.resetAllMocks();
  });

  test('should not render the component when filtersList is empty', () => {
    render(<MethodFilter filtersList={[]} />);
    expect(screen.queryByTestId('sr-method-filters')).toBeNull();
  });

  test('should not render the CardTypes component', () => {
    render(<MethodFilter filtersList={SR_FILTERS.Card} isOptimizerEnabled={true} />);
    expect(screen.getByTestId('sr-method-filters')).toBeInTheDocument();
    expect(screen.queryByTestId('card-types-button')).toBeNull();
  });

  test('should render the CardTypes component', () => {
    render(<MethodFilter filtersList={SR_FILTERS.Card} isOptimizerEnabled={false} />);
    expect(screen.getByTestId('sr-method-filters')).toBeInTheDocument();
    expect(screen.getByText('Card type:')).toBeInTheDocument();

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
    render(
      <MethodFilter
        filtersList={SR_FILTERS.Card}
        selectedGrouping={[
          {
            value: 'network',
            text: 'Card Networks',
            query: 'filter',
          },
        ]}
        isOptimizerEnabled={false}
      />,
    );
    expect(screen.getByTestId('sr-method-filters')).toBeInTheDocument();
    expect(screen.getByText('Filter:')).toBeInTheDocument();
    expect(screen.getByTestId('group-filters-dropdown')).toBeInTheDocument();
    expect(screen.getByText('Card Networks')).toBeInTheDocument();
  });
});
