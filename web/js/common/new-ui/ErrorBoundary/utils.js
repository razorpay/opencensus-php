import { RoutesConfig } from './constants';

/**
 * Get the team name for the given path/route
 * @param  {string} path - The path/route
 * @returns {Object} - The object with route & team name
 */
export const getTeamName = (path) => {
  // Remove /app from start of path
  path = path.replace(/^\/app/, '');
  if (RoutesConfig[path]) {
    return RoutesConfig[path];
  }

  // remove entity id from path and check if path is in RoutesConfig
  // e.g.
  // '/partners/applications/app_1234' => '/partners/applications'
  // '/payments/pay_1234' => '/payments'
  const route = path.substr(0, path.lastIndexOf('/'));
  if (RoutesConfig[route]) {
    return RoutesConfig[route];
  }

  // '/ticket-support/rzpind/I5wF456zbRX5jw/conversation' => '/ticket-support'
  const module = path.substr(0, path.indexOf('/', 1));
  if (RoutesConfig[module]) {
    return RoutesConfig[module];
  }

  // Team is not configured for the route
  return 'Unknown';
};

export const getPathForMetrics = (path = window?.location?.pathname) => {
  try {
    // Remove /app from start of path
    path = path.replace(/^\/app/, '');
    if (RoutesConfig[path]) {
      return path;
    }

    // remove entity id from path and check if path is in RoutesConfig
    // e.g.
    // '/partners/applications/app_1234' => '/partners/applications'
    // '/payments/pay_1234' => '/payments'
    const route = path.substr(0, path.lastIndexOf('/'));
    if (RoutesConfig[route]) {
      return route;
    }

    // '/ticket-support/rzpind/I5wF456zbRX5jw/conversation' => '/ticket-support'
    const module = path.substr(0, path.indexOf('/', 1));
    if (RoutesConfig[module]) {
      return module;
    }

    // Team is not configured for the route
    return 'UNKNOWN_PATH';
  } catch (err) {
    return 'UNKNOWN_PATH';
  }
};
