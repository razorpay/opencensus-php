import React, { useState } from 'react';
import {
  Box,
  Text,
  Tooltip,
  TooltipInteractiveWrapper,
  InfoIcon,
} from '@razorpay/blade/components';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';
import { deleteZone } from 'merchant/reducers/magicCheckout/shippingEngine/action';
import {
  ModalState,
  ShippingEngineStore,
} from 'merchant/reducers/magicCheckout/shippingEngine/types';
import lazy from 'merchant/routes/LazyLoader';
import {
  ZoneName,
  actions,
  ZipCodes,
} from 'merchant/views/MagicCheckout/ShippingSettings/common/cellItem';
import {
  ADD_PROFILE,
  MODAL_TEXTS,
  MODAL_TYPES,
} from 'merchant/views/MagicCheckout/ShippingSettings/constants';
import { SettingsWrapper } from 'merchant/views/MagicCheckout/ShippingSettings/styles';
import CreateButton from 'merchant/views/MagicCheckout/common/components/CreateButton';
import MagicDataTable from 'merchant/views/MagicCheckout/common/components/Datatable';
import PreventDeleteModal from 'merchant/views/MagicCheckout/common/components/PreventDeleteModal';
import { openModal, closeModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';
import { merchantFetch } from 'merchant/utils/ajax';
import { UploadZonesButton } from 'merchant/views/MagicCheckout/ShippingSettings/common/styledComponents/common';

import Modal from './Modal';
import { downloadFromUrl } from 'merchant/views/MagicCheckout/common/helpers';
import { useSplitzService } from 'common/splitz';

const ConfirmationModal = lazy(
  () =>
    import(
      /* webpackChunkName: "MagicZoneSettings" */ 'merchant/views/MagicCheckout/common/components/ConfirmationModal'
    ),
);
const DisplayNotificationTxt = lazy(() =>
  import(
    /* webpackChunkName: "MagicZoneSettings" */ 'merchant/views/MagicCheckout/common/components/ConfirmationModal'
  ).then((module) => ({ default: module.DisplayNotificationTxt })),
);

const Zones = ({
  shippingEngine,
  settings,
  openModal,
  closeModal,
  showNotification,
  deleteZone: deleteZoneAction,
  validateBatch,
  fetchList,
}): JSX.Element => {
  const { abExperiments } = useSplitzService();
  const isZoneUploadEnabled = abExperiments?.magic_zones_file_upload?.variables?.result === 'on';
  const { shipping_engine } = settings;
  const [isModalOpen, setIsModalOpen] = useState<ModalState>(false);
  const { selected_profile, shipping_profiles } = shippingEngine as ShippingEngineStore;
  const [selectedZone, setSelectedZone] = useState<string>('');
  const zones =
    selected_profile && selected_profile !== ADD_PROFILE
      ? shipping_profiles[selected_profile].zones
      : null;

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

  const handleEditClick = (item) => () => {
    setSelectedZone(item.id);
    setIsModalOpen(item.location_count ? MODAL_TYPES.FILE_UPLOAD : MODAL_TYPES.MANUAL);
  };

  const handleDeleteClick = (item) => () => {
    if (zones?.length === 1 && shipping_engine) {
      openModal({
        size: 'small',
        className: `magicToggleConfirmationModal`,
        component: (
          <PreventDeleteModal
            header={MODAL_TEXTS.PREVENT_DELETE_ZONE.header}
            description={MODAL_TEXTS.PREVENT_DELETE_ZONE.description}
          />
        ),
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
              onAffirm={() => deleteZone(item.id)}
            />
          </SuspenseWithLoader>
        ),
      });
    }
  };

  const handleClose = () => {
    setSelectedZone('');
    setIsModalOpen(false);
  };

  const handleCreate = () => {
    setSelectedZone('');
    setIsModalOpen(MODAL_TYPES.MANUAL);
  };

  const handleFileUploadCreate = () => {
    setSelectedZone('');
    setIsModalOpen(MODAL_TYPES.FILE_UPLOAD);
  };

  const showDownloadIcon = (zone) => {
    return !!zone?.location_count;
  };

  const handleDownloadClick = (zone) => {
    if (!zone?.location_count) return;
    merchantFetch({
      url: `1cc/shipping/zones/${zone.id}/download`,
      method: 'get',
    })
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

  return (
    <Box display="flex" gap="spacing.5" flexDirection={{ base: 'column', l: 'row' }}>
      <Box flex="1">
        <Text size="large">
          Shipping zone
          <Text as="span" color="feedback.text.negative.intense">
            *
          </Text>
          <Tooltip
            content="Define specific geographic zones to customize serviceability and set tailored shipping rates. Ensure precise control over where and how you deliver, optimizing costs and customer satisfaction."
            placement="bottom"
          >
            <TooltipInteractiveWrapper>
              <InfoIcon
                color="interactive.icon.gray.muted"
                marginLeft="spacing.2"
                position="relative"
                top="spacing.1"
                size="medium"
              />
            </TooltipInteractiveWrapper>
          </Tooltip>
        </Text>
      </Box>
      <SettingsWrapper>
        {zones?.length ? (
          <MagicDataTable
            data={zones}
            addMoreLabel="zones"
            addMoreLabel2={isZoneUploadEnabled ? 'zipcodes' : undefined}
            handleAddMore={handleCreate}
            handleAddMoreViaFileUpload={isZoneUploadEnabled ? handleFileUploadCreate : undefined}
            columns={[
              ZoneName,
              ZipCodes,
              actions({
                handleDeleteClick,
                handleEditClick,
                downloadable: { showDownloadIcon, handleDownloadClick },
              }),
            ]}
          />
        ) : (
          <>
            <CreateButton entity="zones" onClick={handleCreate} />
            {isZoneUploadEnabled && (
              <UploadZonesButton onClick={handleFileUploadCreate}>
                + Upload zipcodes
              </UploadZonesButton>
            )}
          </>
        )}
        {isModalOpen && (
          <Modal
            zoneId={selectedZone}
            isOpen={isModalOpen}
            closeModal={handleClose}
            validateBatch={validateBatch}
            fetchList={fetchList}
            isModalOpen={isModalOpen}
          />
        )}
      </SettingsWrapper>
    </Box>
  );
};

const mapStateToProps = (state) => ({
  settings: state.magic_settings,
  shippingEngine: state.magicShippingEngine,
});

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      openModal,
      closeModal,
      showNotification,
      deleteZone,
    },
    dispatch,
  );

export default connect(mapStateToProps, mapDispatchToProps)(Zones);
