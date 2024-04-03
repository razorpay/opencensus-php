import React, { useEffect } from 'react';
import { Modal, ModalBody } from '@razorpay/blade/components';

import ErrorBoundary, { Ranks, Teams } from 'common/new-ui/ErrorBoundary';
import BatchUpload from 'merchant/containers/BatchNew/Upload';
import ValidateModalInfo from 'merchant/views/MagicCheckout/ShippingSettings/ProfileSettings/Zones/components/ZonesUpload/ValidateModalInfo';
import {
  DISPLAY_MESSAGES,
  FILE_UPDATE_NOTIFICATION_MSG,
  SAMPLE_FILE_URL,
} from 'merchant/views/MagicCheckout/ShippingSettings/constants';
import { MODAL_MODES } from 'merchant/views/MagicCheckout/common/components/SettingsModal/constants';

import { ZonesUploadProps, ZonePayload, FileUploadResponse } from './types';

const ZonesUpload = (props: ZonesUploadProps): JSX.Element => {
  const {
    closeModal,
    createZoneUpload,
    updateZoneUpload,
    showNotification,
    isOpen,
    itemCategoryId,
    zoneType,
    mode,
    zone,
  } = props;
  const isEditMode: boolean = mode === MODAL_MODES.EDIT;

  useEffect(() => {
    if (isEditMode) {
      showNotification({
        type: 'neutral',
        message: FILE_UPDATE_NOTIFICATION_MSG,
        closeTimeout: 10000,
      });
    }
  }, [isEditMode]);

  const displayFileUploadNotification = (res: FileUploadResponse) => {
    if (res?.success) {
      showNotification({
        type: 'success',
        message: 'File uploaded successfully.',
        closeTimeout: 10000,
      });
    } else {
      showNotification({
        type: 'error',
        message: res.errors ? res.errors[0] : 'Something went wrong.',
        closeTimeout: 10000,
      });
    }
  };

  const validate = (file: File, progressTracker: Record<string, any>) =>
    new Promise((resolve, reject) => {
      const zonePayload: ZonePayload = {
        type: zoneType,
        file,
        progressTracker,
      };
      if (zone) {
        if (zone.name) zonePayload.name = zone.name;
        if (zone.locations) zonePayload.locations = zone.locations;
        if (zone.id) zonePayload.id = zone.id;
      }
      if (itemCategoryId) zonePayload.itemCategoryId = itemCategoryId;
      const actionFn = isEditMode ? updateZoneUpload : createZoneUpload;

      return actionFn(zonePayload)
        .then((res) => {
          resolve({ data: { res } });
          displayFileUploadNotification(res as FileUploadResponse);
          closeModal();
        })
        .catch((e) => {
          showNotification({
            type: 'error',
            message: e.errors ? e.errors[0] : 'Something went wrong.',
            closeTimeout: 10000,
          });
          reject(e);
        });
    });

  return (
    <Modal isOpen={isOpen} onDismiss={closeModal} size="medium">
      <ModalBody>
        <ErrorBoundary team={Teams?.MAGIC_CHECKOUT} rank={Ranks.P0} resetOnProps>
          <BatchUpload
            accept={['csv']}
            closeUrl="/magic/settings/shipping-settings"
            sampleUrl={SAMPLE_FILE_URL}
            title="Upload Zipcodes"
            validateBatch={validate}
            processFile
            displayMsgs={DISPLAY_MESSAGES}
            validateModalInfo={<ValidateModalInfo />}
            maxFileSize={52428800} // 50MB
            batchListClass="shipping-settings-zipcodes-upload"
            onCloseModal={closeModal}
          />
        </ErrorBoundary>
      </ModalBody>
    </Modal>
  );
};

export default ZonesUpload;
