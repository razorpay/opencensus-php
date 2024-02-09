import { ComponentType } from 'react';

import { SpiltzContextState } from 'common/splitz/types';
import NewInvitationJS from 'merchant/views/Account/ManageTeam/components/NewInvitation';

type argsType = {
  email: string;
  role: string;
  sender_name: string;
};

interface NewInvitationProps {
  visibleFields: {
    email: boolean;
    role: boolean;
  };
  defaults: {
    sender_name: string | undefined;
    role: string;
  };
  onSuccess: () => void;
  onFormSubmit: (args: argsType) => void;
  successMsg: (args: argsType) => string;
  ctaText: string;
  experiments?: SpiltzContextState;
  screen?: string;
}
const NewInvitation = NewInvitationJS as ComponentType<NewInvitationProps>;
export default NewInvitation;
