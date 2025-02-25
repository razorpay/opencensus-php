declare const __STAGE__: string;
declare const __BUILD_MODE__: 'legacy' | 'modern';
declare const __APP_VERSION__: string;

declare module '*.svg' {
  const content: any;
  export default content;
}
declare module '*.webp' {
  const content: any;
  export default content;
}
declare module '*.png' {
  const content: any;
  export default content;
}
declare module '*.jpg' {
  const content: any;
  export default content;
}
declare module '*.graphql' {
  import { DocumentNode } from 'graphql';
  // eslint-disable-next-line one-var
  const MyQuery: DocumentNode;

  export { MyQuery };

  export default defaultDocument;
}
