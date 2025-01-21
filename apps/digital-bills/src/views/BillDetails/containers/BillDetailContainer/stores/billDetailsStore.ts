import create from 'zustand';

type BillDetailsState = {
  deleteModalIsOpen: boolean;
  onDeleteModalOpen: () => void;
  onDeleteModalDismiss: () => void;
  onDeleteModalToggle: () => void;
  resendModalIsOpen: boolean;
  onResendModalOpen: () => void;
  onResendModalDismiss: () => void;
  onResendModalToggle: () => void;
};

export const useBillDetailsStore = create<BillDetailsState>((set) => ({
  deleteModalIsOpen: false,
  resendModalIsOpen: false,
  onDeleteModalOpen: (): void => set((state) => ({ ...state, deleteModalIsOpen: true })),
  onDeleteModalDismiss: (): void => set((state) => ({ ...state, deleteModalIsOpen: false })),
  onDeleteModalToggle: (): void =>
    set((state) => ({ ...state, deleteModalIsOpen: !state.deleteModalIsOpen })),
  onResendModalOpen: (): void => set((state) => ({ ...state, resendModalIsOpen: true })),
  onResendModalDismiss: (): void => set((state) => ({ ...state, resendModalIsOpen: false })),
  onResendModalToggle: (): void =>
    set((state) => ({ ...state, resendModalIsOpen: !state.resendModalIsOpen })),
}));
