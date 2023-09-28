import React, { useState } from 'react';
import { connect } from 'react-redux';
import { withRouter } from 'common/deprecated/withRouter';
import { Button, TextInput } from '@razorpay/blade/components';
import ModalHeader from 'common/ui/ModalHeader';
import {
  closeModal as fnCloseModal,
  openModal as fnOpenModal,
} from 'merchant_common/reducers/modals';
import { showNotification as showNotificationProp } from 'merchant_common/reducers/notifications';

import './Payments.styl';
import { merchantFetch } from 'merchant/utils/ajax';

const postEzetapData = async (data) => {
  const dataPromise = await merchantFetch({
    method: 'post',
    absUrl: `/store/app/key`,
    appendModeInURL: false,
    data,
  });
  return dataPromise;
};

const CollectEzetapKeys = ({ closeModal, showNotification, openRefundModal }) => {
  const [data, setData] = useState({
    appKey: '',
    username: '',
  });

  const handleChangeCredentials = (e) => {
    const name = e.name;
    setData({ ...data, [name]: e.value });
  };

  const mutateData = async () => {
    try {
      await postEzetapData(data);
      showNotification({
        type: 'success',
        message: 'Your Ezetap Credentials are saved.',
        closeTimeout: 5000,
      });
      openRefundModal();
    } catch (error) {
      showNotification({
        type: 'error',
        message: error?.response?.data?.errors?.[0] || 'Something Went Wrong',
        closeTimeout: 5000,
      });
    }
  };

  return (
    <>
      <ModalHeader title="Ezetap Credentials" onCloseClick={closeModal} />
      <div className="modal-body ezetap-keys-modal">
        <TextInput name="username" label="UserName" onChange={handleChangeCredentials} />
        <TextInput name="appKey" label="App Key" onChange={handleChangeCredentials} />
        <Button variant="primary" onClick={mutateData}>
          Submit
        </Button>
      </div>
    </>
  );
};

export default withRouter(
  connect(null, {
    openModal: fnOpenModal,
    closeModal: fnCloseModal,
    showNotification: showNotificationProp,
  })(CollectEzetapKeys),
);
