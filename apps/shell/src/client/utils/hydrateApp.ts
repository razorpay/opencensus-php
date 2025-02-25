import { hydrate } from 'react-dom';
import { matchPath } from 'react-router-dom';
import errorService from '@razorpay/universe-cli/errorService';
import { routes } from '@apps/shell/src/client/routes';

const hydrateApp = (appComponent: JSX.Element, rootHTMLElement: HTMLElement | null): void => {
  try {
    const currentRoute = routes.find((route) => matchPath(location.pathname, route.path));
    if (currentRoute) {
      currentRoute.loadableChunk.load().finally(() => {
        hydrate(appComponent, rootHTMLElement);
      });
    } else {
      // if no matching route found, hydrate anyway
      hydrate(appComponent, rootHTMLElement);
    }
  } catch (err: unknown) {
    console.error('error: hydrateApp', err);
    errorService.captureError(`[hydrateApp]: ${(err as Error).message}`, {
      rank: errorService.ErrorRank.P1,
      tags: {},
    });
    hydrate(appComponent, rootHTMLElement);
  }
};

export default hydrateApp;
