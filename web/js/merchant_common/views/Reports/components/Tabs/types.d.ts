import { Location } from 'history';
export interface TabsPropType {
  tabs: {
    /**
     * A string representation of the link location.
     */
    to: string;
    /**
     * When true, the location is matched exactly.
     */
    exact?: boolean;
    /**
     * Tab label.
     */
    label: string;
    /**
     * Component to render when location is matched, should be wrapped with `withRouter`.
     */
    component: ComponentClass<Pick<any, string | number | symbol>, any> &
      WithRouterStatics<(props: any) => Element>;
  }[];
  /**
   * A prefix for `to`.
   */
  basePath?: string;
}

export interface TabPropsType {
  to: string;
  exact: boolean;
  location: Location;
  children: string;
}
