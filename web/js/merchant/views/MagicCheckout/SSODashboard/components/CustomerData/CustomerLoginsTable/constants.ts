import {
  DateCell,
  UTMSourceCell,
} from 'merchant/views/MagicCheckout/SSODashboard/components/Table';

export const TABLE_CELLS = [
  {
    label: 'Date & Time',
    key: 'login_time',
    Component: DateCell,
  },
  {
    label: 'UTM Source',
    key: 'utm_source',
    Component: UTMSourceCell,
  },
];
