import React from 'react';

import { PartnerLoginProps } from './types';
import FormWrapper from './FormWrapper';
import ModalContainer from './ModalContainer';

const PartnerLogin = ({ partner, status }: PartnerLoginProps): JSX.Element => (
  <FormWrapper partner={partner}>
    <ModalContainer partner={partner} status={status} />
  </FormWrapper>
);

export default PartnerLogin;
