import { RouteComponentProps } from 'react-router-dom';

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
