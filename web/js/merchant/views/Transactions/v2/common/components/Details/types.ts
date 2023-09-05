import { RouteComponentProps } from 'react-router-dom';
import { TransactionsEntityRoute } from 'merchant/views/Transactions/v2/common/constants';

export interface DetailsProps extends RouteComponentProps {
  itemId: string;
  baseUrl: string;
  initiatePage: string;
  prevPath: TransactionsEntityRoute;
}
