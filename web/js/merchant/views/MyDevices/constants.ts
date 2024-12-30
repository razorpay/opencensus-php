export const FETCH_DEVICE_ROUTE = '/pos/devices';

export const getUpdateDeviceRoute = (id: string) => {
  return `/pos/devices/${id}/settings`;
};
