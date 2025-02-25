import { lazy, type ComponentType } from 'react';
import { safeChunkImport } from './safeChunkImport';

/**
 * A higher-order function that lazily loads a React component with retry logic. (LazyImport).
 *
 * @param fn - A function that returns a promise, typically a dynamic import for a React component.
 * @returns A lazy-loaded component that will retry loading on failure.
 *
 * @example
 * const loadMyComponent = () => import('./MyComponent');
 *
 * const LazyMyComponent = LazyLoader(loadMyComponent);
 *
 * function App() {
 *   return (
 *     <Suspense fallback={<div>Loading...</div>}>
 *       <LazyMyComponent />
 *     </Suspense>
 *   );
 * }
 */
export const lazyImport = <T extends { default: ComponentType<any> }>(fn: () => Promise<T>) => {
  return lazy(() => safeChunkImport(fn));
};
