import { Refunds } from 'merchant/views/Transactions/v2/Refunds/types';
import { ListContainerProps } from 'merchant/views/Transactions/v2/common/types';

export type RefundsTableProps = ListContainerProps<Refunds['items']>;
