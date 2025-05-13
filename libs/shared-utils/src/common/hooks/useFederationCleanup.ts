import { useLayoutEffect, useCallback } from 'react';
import errorService from '@razorpay/universe-cli/errorService';
import { DASHBOARD_PRIORITY_RANKS, DASHBOARD_TEAMS } from '@libs/shared-types';

/**
 * Handles cleanup for federated components for One Dashboard.
 * Accepts an optional callback that runs on mount and returns a cleanup function.
 *
 * @param name - The name of the federated project
 * @param callback - Optional callback that runs on mount and returns cleanup function
 * @param deps - Optional dependency array for the callback
 *
 * @example
 * useFederationCleanup('payments-dashboard', () => {
 *   console.log('mount');
 *   return () => {
 *     console.log('cleanup');
 *   };
 * }, []);
 */
export const useFederationCleanup = (
  name: string,
  callback: () => (() => void) | undefined,
  deps: unknown[] = [],
) => {
  const memoizedCallback = useCallback(callback, deps);

  useLayoutEffect(() => {
    const captureError = (error: unknown) => {
      errorService.captureError(error, {
        tags: {
          team: DASHBOARD_TEAMS.PLATFORM,
          module: 'useFederationCleanup',
        },
        rank: DASHBOARD_PRIORITY_RANKS.P0,
      });
    };

    let cleanup: (() => void) | undefined;

    if (window.ONE_DASHBOARD) {
      try {
        cleanup = memoizedCallback();
      } catch (error: unknown) {
        captureError(error);
      }

      try {
        const styleTags: NodeListOf<HTMLStyleElement> = document.querySelectorAll(
          `style[data-project="${name}"]`,
        );
        styleTags.forEach((styleTag) => {
          styleTag.disabled = false;
          styleTag.removeAttribute('disabled');
        });

        const linkTags: NodeListOf<HTMLLinkElement> = document.querySelectorAll(
          `link[data-project="${name}"]`,
        );
        linkTags.forEach((linkTag) => {
          linkTag.disabled = false;
          linkTag.removeAttribute('disabled');
        });
      } catch (error: unknown) {
        captureError(error);
      }
    }

    return () => {
      if (window.ONE_DASHBOARD) {
        try {
          cleanup?.();
        } catch (error: unknown) {
          captureError(error);
        }

        try {
          const styleTags: NodeListOf<HTMLStyleElement> = document.querySelectorAll(
            `style[data-project="${name}"]`,
          );
          styleTags.forEach((styleTag) => {
            styleTag.disabled = true;
            styleTag.setAttribute('disabled', 'true');
          });

          const linkTags: NodeListOf<HTMLLinkElement> = document.querySelectorAll(
            `link[data-project="${name}"]`,
          );
          linkTags.forEach((linkTag) => {
            linkTag.disabled = true;
            linkTag.setAttribute('disabled', 'true');
          });
        } catch (error: unknown) {
          captureError(error);
        }
      }
    };
  }, [name, memoizedCallback]);
};
