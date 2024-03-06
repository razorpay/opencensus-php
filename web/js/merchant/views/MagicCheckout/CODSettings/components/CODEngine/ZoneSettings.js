import React, { useCallback, useEffect, useState } from 'react';
import { bindActionCreators } from 'redux';
import { connect } from 'react-redux';

import lazy from 'merchant/routes/LazyLoader';
import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';
import DataTable from 'common/ui/Table/DataTable';

import SettingsLabel from './common/SettingsLabel';
import PreventDeleteModal from 'merchant/views/MagicCheckout/CODSettings/components/CODEngine/common/PreventDeleteModal';
import { zoneCountry, zoneName, zoneStates, actions } from './common/cellItem';

import { openModal, closeModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';
import { deleteZone } from 'merchant/reducers/magicCheckout/codEngine/action';

import { POPOVER_CONTENT, MODAL_MODES } from 'merchant/views/MagicCheckout/CODSettings/constants';
import { downloadFromUrl } from 'merchant/views/MagicCheckout/common/helpers';
import BatchUpload from 'merchant/containers/BatchNew/Upload';
import { ValidateModalInfo } from 'merchant/views/MagicCheckout/CODSettings/components/CODEngine/Allowlist/components/ValidateInfoModal';
import {
  DISPLAY_MESSAGES,
  SAMPLE_FILE_URL,
} from 'merchant/views/MagicCheckout/CODSettings/components/CODEngine/Allowlist/constants';
import {
  validateAllowlist,
  fetchAllowlist,
  downloadAllowlist,
} from 'merchant/reducers/magicCheckout/codEngineAllowlistUpload/actions';
import { useSplitzService } from 'common/splitz';

const CLOSE_URL = '/magic/settings/cod-settings';

const ConfirmationModal = lazy(() =>
  import(
    /* webpackChunkName: "ZoneSettings" */ 'merchant/views/MagicCheckout/common/components/ConfirmationModal'
  ),
);
const DisplayNotificationTxt = lazy(() =>
  import(
    /* webpackChunkName: "ZoneSettings" */ 'merchant/views/MagicCheckout/common/components/ConfirmationModal'
  ).then((module) => ({ default: module.DisplayNotificationTxt })),
);

const ZoneModal = lazy(() => import(/* webpackChunkName: "ZoneSettings" */ './common/ZoneModal'));

function ZoneSettings({
  zones,
  validations,
  openModal,
  closeModal,
  showNotification,
  deleteZoneAction,
  validateBatch,
  fetchList,
  downloadAllowlist,
}) {
  const [errorText, setErrorText] = useState('');
  const { abExperiments } = useSplitzService();
  const isZoneUploadEnabled = abExperiments?.magic_zones_file_upload?.variables?.result === 'on';

  useEffect(() => {
    if (!validations.zones) {
      setErrorText('Required');
    } else {
      setErrorText('');
    }
  }, [validations, zones]);
  const deleteZone = (id) => {
    deleteZoneAction(id)
      .then(() => {
        showNotification({
          type: 'success',
          message: () => (
            <SuspenseWithLoader type="center">
              <DisplayNotificationTxt notificationTxt="Zone deleted successfully" />
            </SuspenseWithLoader>
          ),
        });
      })
      .catch((err) => {
        showNotification({
          type: 'error',
          message: err?.errors[0] || 'Something went wrong',
        });
      })
      .finally(() => {
        closeModal();
      });
  };
  const openZoneModal = (mode = MODAL_MODES.CREATE, id = null) => {
    openModal({
      size: 'medium',
      className: `codSettingModal`,
      component: (
        <SuspenseWithLoader type="center">
          <ZoneModal mode={mode} id={id} />
        </SuspenseWithLoader>
      ),
    });
  };
  const createMoreZones = () => {
    openZoneModal(MODAL_MODES.ADD);
  };
  const onDeleteClick = useCallback(
    (id) => () => {
      if (zones.length === 1) {
        openModal({
          size: 'small',
          className: `magicToggleConfirmationModal`,
          component: <PreventDeleteModal />,
        });
      } else {
        openModal({
          size: 'small',
          className: `magicToggleConfirmationModal`,
          component: (
            <SuspenseWithLoader type="center">
              <ConfirmationModal
                header="Delete zone?"
                desc="Are you sure you want to delete this zone"
                affirmativeLabel="Yes"
                abortLabel="No"
                onAffirm={() => deleteZone(id)}
              />
            </SuspenseWithLoader>
          ),
        });
      }
    },
    [],
  );

  const openFileUpload = (validateBatch, openModal) => {
    openModal({
      size: 'large',
      className: 'cod-engine-allowlist-upload-modal',
      component: (
        <BatchUpload
          accept={['csv']}
          closeUrl={CLOSE_URL}
          title="Upload Zipcodes"
          batchType="cod_engine_allowlist_update"
          validateBatch={validateBatch}
          processFile
          displayMsgs={DISPLAY_MESSAGES}
          validateModalInfo={<ValidateModalInfo sampleUrl={SAMPLE_FILE_URL} />}
          maxFileSize={52428800} // 50MB
          batchListClass="cod-engine-allowlist-upload"
          hideCloseBtn
        />
      ),
    });
  };

  const displayFileUploadNotification = (res) => {
    if (res?.data?.failed) {
      if (res?.data?.count) {
        showNotification({
          type: 'neutral',
          message: `${res.data.count} out of ${
            res.data.count + res.data.failed
          } records processed successfully. Update the ${
            res.data.failed
          } invalid entries by reuploading the file`,
          closeTimeout: 10000,
        });
      } else {
        showNotification({
          type: 'error',
          message: 'Something went wrong. Please try again.',
        });
      }
    } else {
      showNotification({
        type: 'success',
        message: 'File uploaded successfully.',
        closeTimeout: 10000,
      });
    }
  };

  const validate = (file, progressTracker) =>
    new Promise((resolve, reject) => {
      return validateBatch(file, progressTracker)
        .then((res) => {
          resolve({ data: { file } });
          displayFileUploadNotification(res);
          fetchList({
            skip: 0,
            count: 25,
          }).then(() => {
            closeModal();
          });
        })
        .catch((error) => {
          reject(error);
        });
    });

  const onEditClick = (id) => () => {
    const allowlistFileZone = zones.find((zone) => zone.id === id);
    allowlistFileZone && allowlistFileZone.name === 'Zipcodes Uploaded'
      ? openFileUpload(validate, openModal)
      : openZoneModal(MODAL_MODES.EDIT, id);
  };

  const showDownloadIcon = (zone) => {
    return zone?.name === 'Zipcodes Uploaded';
  };

  const handleDownloadClick = () => {
    downloadAllowlist()
      .then((res) => {
        const file_link = res.data.file_link;
        downloadFromUrl(showNotification, file_link);
      })
      .catch((err) => {
        showNotification({
          type: 'error',
          message: err?.errors?.[0] || 'Something went wrong while downloading file',
        });
      });
  };

  const dataTableActionsArgs = () => {
    let args = {
      onEditClick,
      onDeleteClick,
    };
    if (isZoneUploadEnabled)
      args = {
        ...args,
        downloadable: { showDownloadIcon, handleDownloadClick },
      };
    return args;
  };

  return (
    <div className="cod-setting-item">
      <SettingsLabel
        value="COD zones"
        required
        popoverContent={POPOVER_CONTENT.zones}
        errorText={errorText}
      />
      <div className="cod-options-container">
        {zones.length === 0 ? (
          <button onClick={openZoneModal} className="add-config-button">
            + Add zones
          </button>
        ) : (
          <div className="cod-table-wrapper">
            <DataTable
              customClass="settings-table"
              items={zones}
              columns={[zoneName, zoneCountry, zoneStates, actions(dataTableActionsArgs())]}
            />
            <p onClick={createMoreZones} className="add-more-button">
              + Create more zones
            </p>
          </div>
        )}
      </div>
    </div>
  );
}
const mapStateToProps = (state) => ({
  zones: state.magicCODEngine.zones,
  validations: state.magicCODEngine.validations,
});

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      openModal,
      closeModal,
      showNotification,
      deleteZoneAction: deleteZone,
      validateBatch: validateAllowlist,
      fetchList: fetchAllowlist,
      downloadAllowlist,
    },
    dispatch,
  );

export default connect(mapStateToProps, mapDispatchToProps)(ZoneSettings);
