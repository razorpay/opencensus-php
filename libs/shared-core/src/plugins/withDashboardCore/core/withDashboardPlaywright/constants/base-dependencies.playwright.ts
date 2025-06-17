type BASE_DEPENDENCIES_TYPE = { namespace: string; service_name: string }[];

export const BASE_DEPENDENCIES: BASE_DEPENDENCIES_TYPE = [
  { namespace: 'edge', service_name: 'edge-base' },
  { namespace: 'frontend-graphql', service_name: 'frontend-graphql-base' },
  { namespace: 'ufh', service_name: 'ufh-base' },
  { namespace: 'wallet', service_name: 'wallet-base' },
  // { namespace: 'splitz', service_name: 'splitz-base' },
  { namespace: 'gcoms', service_name: 'gcoms-base' },
  { namespace: 'asv', service_name: 'asv-web-base' },
  { namespace: 'rize-service', service_name: 'rize-service-web-base' },
  { namespace: 'pgos', service_name: 'pgos-base' },
  // can add multiple pod name dependencies in a namespace
  // { namespace: 'partnerships', service_name: 'partnerships-test-base' },
  // { namespace: 'partnerships', service_name: 'partnerships-live-base' },
  // { namespace: 'ui-config-service', service_name: 'ui-config-service-base' },
  { namespace: 'gimli', service_name: 'gimli-base' },
  { namespace: 'reminders', service_name: 'reminders-base' },
  { namespace: 'scrooge', service_name: 'scrooge-web-base' },
  { namespace: 'ui-config-service', service_name: 'ui-config-service-base' },
  { namespace: 'care', service_name: 'care-web-base' },
  { namespace: 'partnerships', service_name: 'partnerships-live-base' },
  { namespace: 'no-code-apps', service_name: 'no-code-apps-base' },
  { namespace: 'splitz', service_name: 'splitz-base' },
  { namespace: 'subscriptions', service_name: 'subscriptions-web-base' },
  { namespace: 'terminals', service_name: 'terminals-live-base' },
  { namespace: 'pg-router', service_name: 'pg-router-base' },
  { namespace: 'api', service_name: 'api-web-base' },
];

export type { BASE_DEPENDENCIES_TYPE };
