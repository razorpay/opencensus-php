export type OpenModalPayload = {
  size?: 'regular' | 'small' | 'medium' | 'med-large' | 'large' | 'xlarge' | 'custom';
  component: JSX.Element;
  className?: string;
  overlayStyles?: Record<string, string>;
  isNew?: boolean;
};

export type OpenModalType = (arg0: OpenModalPayload) => void;
export type CloseModalType = (arg0?: OpenModalPayload) => void;

export type ModalActions = {
  openModal: OpenModalPayload;
  closeModal: CloseModalType;
};

export type ModalReducerState = Partial<OpenModalPayload>;
