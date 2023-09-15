import { RouteComponentProps } from 'react-router-dom';

import { TransactionsEntityRoute } from 'merchant/views/Transactions/v2/common/constants';

export interface HandleDetailsClickParams {
  itemId: string;
  baseUrl: string;
  initiatePage: string;
  prevPath: TransactionsEntityRoute;
  history: RouteComponentProps['history'];
  isButton?: boolean;
}

export interface DetailsProps extends RouteComponentProps, HandleDetailsClickParams {}
