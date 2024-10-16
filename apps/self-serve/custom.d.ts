declare module '*.svg' {
  const content: any;
  export default content;
}

declare module 'react-lottie';

interface Window {
  cdnBaseUrl: string;
}
