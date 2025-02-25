declare const __BUILD_MODE__: 'legacy' | 'modern';
declare const __STAGE__: string;
declare const __APP_VERSION__: string;

declare module '*.svg' {
  const content: any;
  export default content;
}

interface MerchantFetchArgs<T> {
  url: string;
  contact: 'POST' | 'GET' | 'PUT' | 'DELETE';
  mode: 'live' | 'test';
  data: T;
}
declare function merchantFetch<T>(args: MerchantFetchArgs<T>): Promise<unknown>;
