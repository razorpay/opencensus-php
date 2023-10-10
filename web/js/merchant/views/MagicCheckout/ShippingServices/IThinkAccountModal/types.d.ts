export type DemoVideoPropsType = {
  infoText: string;
  demoVideoLink: string;
};

export type InfoPointsType = {
  step: number;
  setStep: (arg: number) => void;
};

export type IThinkFormPropType = {
  step: number;
  setStep: (arg: number) => void;
  createProviders: (arg: Record<string, any>) => any;
  displayNotification: (arg: Record<string, any>) => any;
  closeModal: () => void;
  user: Record<string, any>;
};

export type DemoVideoContainerPropTypes = {
  demoVideo: (props: DemoVideoPropsType) => JSX.Element;
  modalIcon: string;
  demoInfoText: string;
  demoVideoLink: string;
};

export type LinkAccountPropsType = {
  closeModal: () => void;
  step: number;
  setStep: (arg: number) => void;
};

export type IThinkModalPropsType = {
  closeModal: () => void;
};
