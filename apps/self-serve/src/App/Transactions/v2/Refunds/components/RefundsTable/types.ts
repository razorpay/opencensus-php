import { Refunds } from 'apps/self-serve/src/App/Transactions/v2/Refunds/types';
import { ListContainerProps } from 'apps/self-serve/src/App/Transactions/v2/common/types';

export type RefundsTableProps = ListContainerProps<Refunds['items']>;
