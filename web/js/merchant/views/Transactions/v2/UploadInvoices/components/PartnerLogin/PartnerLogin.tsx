import React from 'react';
import { connect } from 'react-redux';
import { compose } from 'redux';

import { getUser } from 'shell/commonStore';
import { showNotification } from 'merchant_common/reducers/notifications';

import { PartnerLoginProps } from './types';
import FormWrapper from './FormWrapper';
import ModalContainer from './ModalContainer';

const PartnerLogin = ({ partner, status, showNotification }: PartnerLoginProps): JSX.Element => {
  const user = getUser() as { gstin?: string };
  return (
    <FormWrapper partner={partner} user={user}>
      <ModalContainer partner={partner} status={status} showNotification={showNotification} />
    </FormWrapper>
  );
};

export default compose(
  connect(null, {
    showNotification,
  }),
)(PartnerLogin);
