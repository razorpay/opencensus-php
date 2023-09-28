import { RouteComponentProps } from 'common/deprecated/RouteComponentProps';

export interface EntitiesOverviewProps extends RouteComponentProps {
  mode: 'live' | 'test';
}
