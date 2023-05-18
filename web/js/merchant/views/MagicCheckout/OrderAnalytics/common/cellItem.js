import { i18HumanReadableCurrency } from 'common/utils/numerals';
import { paiseToRupees } from 'common/utils/rzp-utils';
// eslint-disable-next-line import/no-cycle
import { getOrderPercentage } from 'merchant/views/MagicCheckout/OrderAnalytics/utils';
import { UTM_SOURCE_ICONS } from 'merchant/views/MagicCheckout/OrderAnalytics/constants';

export const source = {
  title: 'Source',
  value: (item) => {
    const val = item.source ?? item.label ?? '-';
    const icon = val?.trim()?.toLowerCase();
    return (
      <p className="utm-source">
        {val}
        {UTM_SOURCE_ICONS[icon] ? (
          <span className="source-icon">
            <img alt="source-icon" className={icon} src={UTM_SOURCE_ICONS[icon]} />
          </span>
        ) : null}
      </p>
    );
  },
  columnClass: 'utm-table-item',
};

export const medium = {
  title: 'Medium',
  value: (item) => item.medium ?? item.label ?? '-',
  columnClass: 'utm-table-item',
};

export const campaign = {
  title: 'Campaign',
  value: (item) => <p title={item.label}>{item.label ?? '-'}</p>,
  columnClass: 'utm-table-item',
};

export const total_orders = {
  title: 'Number of orders',
  value: (item) => (
    <p>
      {item.order_count ?? '-'}
      <span className="order-percent">({getOrderPercentage(item)}%)</span>
    </p>
  ),
  columnClass: 'utm-table-item',
};

export const total_sales = {
  title: 'Sales',
  value: (item) => i18HumanReadableCurrency(paiseToRupees(item.sales || 0), 'INR'),
  columnClass: 'utm-table-item',
};

export const total_gmv = {
  title: 'GMV',
  value: (item) => i18HumanReadableCurrency(paiseToRupees(item.gmv || 0), 'INR'),
  columnClass: 'utm-table-item',
};

export const product_name = {
  title: 'Name',
  value: (item) => item.label || '-',
  columnClass: 'product-name',
};
export const product_qty = { title: 'Quantity', value: (item) => item.qty };
