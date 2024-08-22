export interface Route {
  path: string;
  element: JSX.Element;
  children?: Route[];
}

export interface ModuleRouteProps {
  localBuild?: boolean;
}
