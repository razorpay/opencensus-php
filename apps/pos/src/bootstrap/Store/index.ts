import create from 'zustand';
import { CartDeviceType, PosAppStateType } from '../../app/types';

const posStore = (set) => {
  const state: PosAppStateType = {
    selectedDevices: [],
    addDevice: (device: CartDeviceType) => {
      set((prevState) => structuredClone(prevState).push(device));
    },
    removeDevice: (deviceIdx: number) => {
      set((prevState) => {
        const newState = structuredClone(prevState);
        delete newState[deviceIdx];
        return newState;
      });
    },
    editDevice: (device: CartDeviceType, deviceIdx: number) => {
      set((prevState) => {
        const newState = structuredClone(prevState);
        newState[deviceIdx] = device;
        return newState;
      });
    },
  };
  return state;
};

const usePosStore = create(posStore);

export default usePosStore;
