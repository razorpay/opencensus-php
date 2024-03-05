import { RouteComponentProps } from 'apps/self-serve/src/App/Transactions/v2/Payments/types';

export interface EntitiesOverviewProps extends RouteComponentProps {
  mode: 'live' | 'test';
}
