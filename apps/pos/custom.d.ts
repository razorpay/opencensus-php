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
