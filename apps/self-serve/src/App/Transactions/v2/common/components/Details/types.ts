import { Location, NavigateFunction } from 'react-router-dom';

import { TransactionsEntityRoute } from 'apps/self-serve/src/App/Transactions/v2/common/constants';

export interface RouterParams {
  location: Location;
  navigate: NavigateFunction;
}
export interface HandleDetailsClickParams {
  itemId: string;
  baseUrl: string;
  initiatePage: string;
  prevPath: TransactionsEntityRoute;
  isButton?: boolean;
  isDisabled?: boolean;
}

export type DetailsProps = HandleDetailsClickParams;
