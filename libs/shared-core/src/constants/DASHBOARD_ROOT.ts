if (!process.env.NX_WORKSPACE_ROOT) {
  throw new Error('NX_WORKSPACE_ROOT not found, please execute your command via nx.');
}

export const DASHBOARD_ROOT = process.env.NX_WORKSPACE_ROOT;
