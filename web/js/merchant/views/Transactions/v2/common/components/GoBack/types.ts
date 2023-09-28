import { RouteComponentProps } from 'common/deprecated/RouteComponentProps';

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
