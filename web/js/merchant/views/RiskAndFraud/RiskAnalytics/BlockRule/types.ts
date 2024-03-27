import { User } from 'common/typings';

import { AnalyticsEntity } from '../types';

export type RequestBlacklistProps = {
  user: User;
  isOpen: boolean;
  entity: AnalyticsEntity;
  onDismiss: () => void;
  showNotification: (payload: { type: string; message: string }) => void;
};

export type FormValues = {
  parameters: string | undefined;
  comments: string;
  email: string;
  file: File | null;
};

export type FormError = Partial<{
  parameters: string;
  comments: string;
  email: string;
  file: string;
}>;

export type UploadButtonProps = {
  value: File | null;
  fileError: string | null;
  onFileChange: (file: File | null) => void;
};

export type SuccessPopupProps = {
  isOpen: boolean;
  ticketId: string;
  onDismiss: () => void;
};

export type CreateFdTicket = {
  user: User;
  formValues: FormValues;
};

export type BlockRuleProps = {
  entity: AnalyticsEntity;
};
