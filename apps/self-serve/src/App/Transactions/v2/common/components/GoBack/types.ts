import { RouteComponentProps } from 'apps/self-serve/src/App/Transactions/v2/Payments/types';

type LocationState =
  | undefined
  | {
      prevPath?: string;
    };

export interface GoBackProps
  extends RouteComponentProps<
    Record<string, string | undefined>,
    Record<string, unknown>,
    LocationState
  > {
  onClickCb?: () => void;
}
