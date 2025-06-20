/**
 * Type declarations for module imports that TypeScript doesn't recognize natively
 * This file provides type definitions for non-JavaScript/TypeScript files like SVGs
 * so they can be imported without TypeScript errors
 */
declare module '*.svg' {
  const path: string;
  export default path;
}

declare module '*.png' {
  const path: string;
  export default path;
}
