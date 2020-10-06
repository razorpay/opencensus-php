declare module '*.svg' {
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
