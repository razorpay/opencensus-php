import AmountBreakup from 'merchant/views/Transactions/Orders/components/AmountBreakup';
import { render, fireEvent, screen } from 'test-utils';
import { order } from './constants';
import { getFormattedAmount } from 'common/utils/rzp-utils';

describe('Orders AmountBreakup component', () => {
  const expandBreakupComponent = (props = {}) => {
    render(
      <AmountBreakup
        order={{
          ...order,
          ...props,
          status: 'attempted',
        }}
      />,
    );
    const collapseBtn = screen.getByRole('button');
    expect(collapseBtn).toBeInTheDocument();
    // toggling button to change isBreakupVisible to true
    fireEvent.click(collapseBtn);
  };

  it('should show order amount', () => {
    expandBreakupComponent();
    expect(screen.getByText(/Order Amount/i)).toBeInTheDocument();
    const [rupee, paisa] = getFormattedAmount(order.line_items_total).split('.');
    expect(screen.getAllByText(new RegExp(rupee, 'i'))[0]).toBeInTheDocument();
    expect(screen.getAllByText(new RegExp(`.${paisa}`, 'i'))[0]).toBeInTheDocument();
  });

  it('should show shipping charges', () => {
    expandBreakupComponent({
      offer: null,
    });
    expect(screen.getByText(/Shipping Charges/i)).toBeInTheDocument();
    const [rupee, paisa] = getFormattedAmount(order.shipping_fee).split('.');
    expect(screen.getAllByText(new RegExp(rupee, 'i'))[0]).toBeInTheDocument();
    expect(screen.getAllByText(new RegExp(`.${paisa}`, 'i'))[0]).toBeInTheDocument();
  });

  it('should show offer name if defined', () => {
    expandBreakupComponent();
    expect(screen.getByText(new RegExp(order.offer.name, 'i'))).toBeInTheDocument();
    expect(screen.getByText(new RegExp(order.offer.id, 'i'))).toBeInTheDocument();
  });

  it('should show promotion code if defined', () => {
    expandBreakupComponent({
      shipping_fee: 0,
    });
    expect(screen.getByText(/Coupon/i)).toBeInTheDocument();
    expect(screen.getByText(new RegExp(order.promotions[0].code, 'i'))).toBeInTheDocument();
  });

  it('should show taxes code if defined', () => {
    expandBreakupComponent({
      shipping_fee: 0,
    });
    expect(screen.getByText(/Taxes/i)).toBeInTheDocument();
  });

  it('should show the magic prepay discount', () => {
    expandBreakupComponent({
      promotions: [
        ...order.promotions,
        {
          type: 'prepay_discount',
          value: 10000,
        },
      ],
    });
    expect(screen.getByText(/^COD to Prepaid discount?/i)).toBeInTheDocument();
  });

  it('should show COD charge', () => {
    expandBreakupComponent({ cod_fee: 50 });
    expect(screen.getByText(/^COD charges?/i)).toBeInTheDocument();
  });
});
