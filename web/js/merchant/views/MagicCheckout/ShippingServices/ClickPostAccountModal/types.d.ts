export type ClickpostFormPropType = {
  createProviders: (arg: Record<string, any>) => any;
  showNotification: (arg: Record<string, any>) => any;
  closeModal: () => void;
  user: Record<string, any>;
};

export type ClickpostFormDataType = {
  username: string;
  password: string;
};

export type ClickpostModalPropsType = {
  closeModal: () => void;
};

export type LinkAccountPropsType = {
  closeModal: () => void;
};

export type ClickpostFormGlobalStateType = {
  session: {
    user: {
      id: string;
    };
  };
};
