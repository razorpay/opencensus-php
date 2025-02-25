declare const __BUILD_MODE__: 'legacy' | 'modern';
declare const __STAGE__: string;
declare const __APP_VERSION__: string;

declare module '*.svg' {
  const content: any;
  export default content;
}

declare module 'react-lottie';