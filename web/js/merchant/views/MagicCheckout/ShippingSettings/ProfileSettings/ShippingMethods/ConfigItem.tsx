import React from 'react';
import { bindActionCreators } from 'redux';
import { connect } from 'react-redux';

import lazy from 'merchant/routes/LazyLoader';
import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';
import { Box, Link, PlusIcon, Text } from '@razorpay/blade/components';

import { ConfigItemWrapper } from './styles';
import ShippingMethodsTable from './ShippingMethodsTable';
import { openModal, closeModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';
import { deleteShippingMethod } from 'merchant/reducers/magicCheckout/shippingEngine/action';
import { MODAL_MODES } from 'merchant/views/MagicCheckout/common/components/SettingsModal/constants';
import { useFormContext } from './FormContext';

const ConfirmationModal = lazy(
  () =>
    import(
      /* webpackChunkName: "MagicShippingEngineConfigItem" */ 'merchant/views/MagicCheckout/common/components/ConfirmationModal'
    ),
);
const DisplayNotificationTxt = lazy(() =>
  import(
    /* webpackChunkName: "MagicShippingEngineConfigItem" */ 'merchant/views/MagicCheckout/common/components/ConfirmationModal'
  ).then((module) => ({ default: module.DisplayNotificationTxt })),
);

const ConfigItem = ({
  zone,
  handleClick,
  openModal,
  closeModal,
  deleteShippingMethod,
}): JSX.Element => {
  const { resetForm } = useFormContext();

  const handleOnClick = (mode = MODAL_MODES.CREATE) => {
    if (mode === MODAL_MODES.CREATE) {
      resetForm();
    }
    handleClick(zone, mode);
  };

  const hasMethods = zone?.shipping_methods?.length > 0;

  const deleteMethod = (id) => {
    deleteShippingMethod(id, zone.id)
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

  const handleDelete = (id) => {
    openModal({
      size: 'small',
      className: `magicToggleConfirmationModal`,
      component: (
        <SuspenseWithLoader type="center">
          <ConfirmationModal
            header="Delete shipping method?"
            desc="Are you sure you want to delete this method"
            affirmativeLabel="Yes"
            abortLabel="No"
            onAffirm={() => deleteMethod(id)}
          />
        </SuspenseWithLoader>
      ),
    });
  };

  return (
    <ConfigItemWrapper>
      <Box
        display="flex"
        flexDirection={hasMethods ? 'column' : 'row'}
        justifyContent="space-between"
        flex="1"
      >
        <Box>
          <Text size="large">{zone?.name}</Text>
        </Box>
        {hasMethods ? (
          <Box>
            <ShippingMethodsTable
              shipping_methods={zone.shipping_methods}
              handleEdit={() => handleOnClick(MODAL_MODES.EDIT)}
              handleDelete={handleDelete}
            />
            <Link
              marginTop="spacing.4"
              variant="button"
              icon={PlusIcon}
              onClick={() => handleOnClick(MODAL_MODES.CREATE)}
            >
              Add shipping method
            </Link>
          </Box>
        ) : (
          <Link variant="button" icon={PlusIcon} onClick={() => handleOnClick(MODAL_MODES.CREATE)}>
            Add shipping method
          </Link>
        )}
      </Box>
    </ConfigItemWrapper>
  );
};

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      openModal,
      closeModal,
      showNotification,
      deleteShippingMethod,
    },
    dispatch,
  );

export default connect(null, mapDispatchToProps)(ConfigItem);
