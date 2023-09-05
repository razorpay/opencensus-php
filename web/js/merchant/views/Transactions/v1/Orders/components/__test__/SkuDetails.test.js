import SkuDetials from 'merchant/views/Transactions/v1/Orders/components/SkuDetails';
import { render, fireEvent, screen } from 'test-utils';
import { getOrderLineItems } from './constants';

describe('Orders - SkuDetails Component', () => {
  test('should show first 3 items when collapsed', () => {
    const line_items = getOrderLineItems(6);
    render(<SkuDetials line_items={line_items} />);
    line_items.forEach(({ sku }, index) => {
      const itemNumber = index + 1;
      if (itemNumber <= 3) {
        expect(screen.queryByText(new RegExp(sku, 'i'))).toBeInTheDocument();
      } else {
        expect(screen.queryByText(new RegExp(sku, 'i'))).not.toBeInTheDocument();
      }
    });
  });

  test('should show all items in rows of 3 when expanded', () => {
    const line_items = getOrderLineItems(11);
    render(<SkuDetials line_items={line_items} />);
    const button = screen.getByRole('button');
    expect(button).toBeInTheDocument();
    fireEvent.click(button);

    const items_cols = [];
    for (let index = 0; index < line_items.length; index += 3) {
      items_cols.push(line_items.slice(index, index + 3).map((item) => item.sku));
    }
    items_cols.forEach((row) => {
      expect(screen.queryByText(new RegExp(row.join(','), 'i'))).toBeInTheDocument();
    });
  });

  describe('Collapse button', () => {
    test('should be shown if line_items is more than 3', () => {
      const line_items = getOrderLineItems(11);
      render(<SkuDetials line_items={line_items} />);
      const button = screen.queryByRole('button');
      expect(button).toBeInTheDocument();
    });

    test('should not be shown if line_items is less or equal to 3', () => {
      const line_items = getOrderLineItems(3);
      render(<SkuDetials line_items={line_items} />);
      const button = screen.queryByRole('button');
      expect(button).not.toBeInTheDocument();
    });
  });
});
