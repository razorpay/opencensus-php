export type UnicommerceFormPropType = {
  createProviders: (arg: Record<string, any>) => any;
  displayNotification: (arg: Record<string, any>) => any;
  closeModal: () => void;
  user: Record<string, any>;
};

export type UnicommerceFormDataType = {
  username: string;
  password: string;
  tenant: string;
};

export type UnicommerceModalPropsType = {
  closeModal: () => void;
};

export type LinkAccountPropsType = {
  closeModal: () => void;
};

export type UnicommerceFormGlobalStateType = {
  session: {
    user: {
      id: string;
    };
  };
};
