interface EmailNotificationReduxProps {
  user: {
    role: string;
    user: {
      email: string;
    };
  };
  org: {
    features: Array<string>;
  };
  config: {
    config: any;
  };
}

interface EmailNotificationDispatchProps {
  showNotification: (notification: any) => void;
  updateEmailSettings: (data: any) => Promise<any>;
  openModal: (options: any) => void;
  closeModal: () => void;
  updateEmailSettingsInStore: (data: any) => void;
}

interface InjectedEmailNotificationReduxFormProps {
  initialize: (config: any) => void;
  handleSubmit: (handler: any) => (e: React.FormEvent) => void;
}

type EmailNotificationProps = EmailNotificationReduxProps &
  EmailNotificationDispatchProps &
  InjectedEmailNotificationReduxFormProps;

export {
  EmailNotificationReduxProps,
  EmailNotificationDispatchProps,
  InjectedEmailNotificationReduxFormProps,
};
export default EmailNotificationProps;
