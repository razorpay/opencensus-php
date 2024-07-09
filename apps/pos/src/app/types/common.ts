export type APIResponse<T, E> = {
  status_code: number;
  success: boolean;
  data?: T;
  errors?: E[];
  code?: string;
};

export type RouteConfig = {
  fallback: string;
  child: {
    route: string;
    view: JSX.Element;
  }[];
};
