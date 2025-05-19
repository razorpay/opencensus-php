import {
  BaseCell,
  DateCell,
  UTMSourceCell,
} from 'merchant/views/MagicCheckout/SSODashboard/components/Table';
import CustomerDetails from 'merchant/views/MagicCheckout/SSODashboard/components/CustomerData/CustomerDetails';

export const TABLE_CELLS = [
  {
    label: 'Customer Details',
    key: 'customer_id',
    Component: CustomerDetails,
  },
  {
    label: 'Phone',
    key: 'phone',
    Component: BaseCell,
  },
  {
    label: 'Email',
    key: 'email',
    Component: BaseCell,
  },
  {
    label: 'First Login',
    key: 'first_login',
    Component: DateCell,
  },
  {
    label: 'Last Login',
    key: 'last_login',
    Component: DateCell,
  },
  {
    label: 'Frequency',
    key: 'login_frequency',
    Component: BaseCell,
  },
  {
    label: 'UTM Source',
    key: 'utm_source',
    Component: UTMSourceCell,
  },
  {
    label: 'Last order',
    key: 'last_order',
    Component: DateCell,
  },
  {
    label: 'Abandoned Checkout',
    key: 'abandoned_checkout',
    Component: DateCell,
  },
];
