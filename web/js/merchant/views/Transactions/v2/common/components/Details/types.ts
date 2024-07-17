import { Location, NavigateFunction } from 'react-router-dom';

import { TransactionsEntityRoute } from 'merchant/views/Transactions/v2/common/constants';

export interface RouterParams {
  location: Location;
  navigate: NavigateFunction;
}
export interface HandleDetailsClickParams {
  itemId: string;
  baseUrl: string;
  initiatePage: string;
  prevPath: TransactionsEntityRoute;
  prevSearch?: string;
  isButton?: boolean;
  isDisabled?: boolean;
  rowData?: { paymentMethod?: string; sourceChannel?: string };
}

export type DetailsProps = HandleDetailsClickParams;
