import create from 'zustand';

import { convertStoreResponseToForm } from 'merchant/views/StoreSettings/StoreCreateOrEdit/hooks/useStoreCreateMutation';
import { StoreCreateFormValues } from 'merchant/views/StoreSettings/StoreCreateOrEdit/types';

type StoresCreateFormStore = {
  basicInfoForm: StoreCreateFormValues;
  deleteTerminalModal: {
    isOpen: boolean;
    terminalId: string;
    index: number;
  };
};
const storeCreateState: StoresCreateFormStore = {
  basicInfoForm: convertStoreResponseToForm(null),
  deleteTerminalModal: {
    isOpen: false,
    terminalId: '',
    index: 0,
  },
};

type StoresCreateState = {
  basicInfoForm: StoreCreateFormValues;
  setBasicInfoForm: (form: StoreCreateFormValues) => void;
  deleteTerminalModal: {
    isOpen: boolean;
    terminalId: string;
    index: number;
  };
  setDeleteTerminalModal: (modal: {
    isOpen?: boolean;
    terminalId?: string;
    index?: number;
  }) => void;
  neglectStateAndCityCheck: boolean;
  setNeglectStateAndCityCheck: (flag: boolean) => void;
};

export const useStoresCreateStore = create<StoresCreateState>((set) => ({
  basicInfoForm: storeCreateState.basicInfoForm,
  deleteTerminalModal: storeCreateState.deleteTerminalModal,
  neglectStateAndCityCheck: false,
  setDeleteTerminalModal: (modal) =>
    set((state) => ({ ...state, deleteTerminalModal: { ...state.deleteTerminalModal, ...modal } })),
  setBasicInfoForm: (form) =>
    set((state) => ({ ...state, basicInfoForm: { ...state.basicInfoForm, ...form } })),
  setNeglectStateAndCityCheck: (flag) =>
    set((state) => ({ ...state, neglectStateAndCityCheck: flag })),
}));
