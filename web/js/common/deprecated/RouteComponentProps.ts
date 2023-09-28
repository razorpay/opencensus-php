import { Params, NavigateFunction, To, NavigateOptions } from 'react-router-dom';

/**
 * @deprecated Use hooks from react-router-dom instead: useNavigate, useLocation, useMatch
 */
export interface RouteComponentProps<Params = any, C = any, S = any> {
  history?: any;
  location?: any;
  match?: any;
}

export interface WithRouterProps {
  location?: Location;
  params?: Readonly<Params<string>>;
  match?: {
    params: Readonly<Params<string>>;
  };
  navigate?: NavigateFunction;
  history?: {
    push: (x: To & Pick<NavigateOptions, 'state'>) => void;
    replace: (path: any) => void;
    go: NavigateFunction;
    goBack: () => void;
    location: Location;
  };
}
