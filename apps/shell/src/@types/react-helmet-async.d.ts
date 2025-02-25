declare module 'react-helmet-async' {
  interface HelmetPropertyMethod {
    toComponent: () => JSX.Element;
  }
  export interface HelmetContext {
    helmet?: {
      base: HelmetPropertyMethod;
      title: HelmetPropertyMethod;
      meta: HelmetPropertyMethod;
      link: HelmetPropertyMethod;
      script: HelmetPropertyMethod;
    };
  }
}
