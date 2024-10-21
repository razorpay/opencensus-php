import { BadgeProps } from '@razorpay/blade/components';
import { Payments, Item } from 'merchant/views/Transactions/v2/UploadInvoices/types';
import { Paginate } from 'merchant/views/Transactions/v2/common/types';

type ListContainerProps<T> = {
  count: number;
  skip: number;
  paginate: Paginate;
  loading?: boolean;
  items?: T[];
  shouldShowCustomTransactionTabView?: boolean;
  selectedColumnsList?: string[];
  isOmniView?: boolean;
};

export type PaymentsTableProps = ListContainerProps<Payments['items']>;

export type StatusProps = {
  variant: BadgeProps['color'];
  content: string;
  status: Item['status'];
};

export type InvoiceActionProps = {
  item: Item;
  showNotification: (args: { type: string; message: string }) => void;
  updateExportPayment: (item: Item) => void;
};

export type BuyerAddressActionProps = {
  id: Item['id'];
  status: Item['status'];
  senderDetails: Item['sender_details'];
  openModal: (args: { component: JSX.Element; size: string }) => void;
  closeModal: () => void;
  showNotification: (args: { type: string; message: string }) => void;
};
