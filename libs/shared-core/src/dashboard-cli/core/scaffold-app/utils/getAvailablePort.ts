import { DASHBOARD_FEDERATED_MODULE_CONFIGS } from '@src/configs';

export const getAvailablePort = () => {
  const excludedPorts = Object.values(DASHBOARD_FEDERATED_MODULE_CONFIGS).map(
    (remote) => remote.devServerPort,
  );
  const minPort = 8000;
  const maxPort = 9999;

  if (excludedPorts.length >= maxPort - minPort + 1) {
    throw new Error('No available ports in the given range.');
  }

  let port: number;
  let attempts = 0;
  const maxAttempts = 1000;

  do {
    const basePort =
      excludedPorts.length > 0
        ? excludedPorts[Math.floor(Math.random() * excludedPorts.length)]
        : minPort;

    const offset = Math.floor(Math.random() * 20) - 10;
    port = Math.min(Math.max(basePort + offset, minPort), maxPort);

    if (excludedPorts.includes(port)) {
      port = Math.floor(Math.random() * (maxPort - minPort + 1)) + minPort;
    }

    attempts++;
    if (attempts > maxAttempts) {
      throw new Error('Could not find an available port.');
    }
  } while (excludedPorts.includes(port));

  return port;
};
