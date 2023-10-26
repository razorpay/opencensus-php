import React, { useState } from 'react';
import {
  Box,
  Heading,
  Text,
  Tooltip,
  TooltipInteractiveWrapper,
  InfoIcon,
} from '@razorpay/blade/components';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';
import { deleteCategory } from 'merchant/reducers/magicCheckout/shippingEngine/action';
import { ShippingEngineStore } from 'merchant/reducers/magicCheckout/shippingEngine/types';
import lazy from 'merchant/routes/LazyLoader';
import {
  CategoryName,
  ProductCount,
  actions,
} from 'merchant/views/MagicCheckout/ShippingSettings/common/cellItem';
import { ADD_PROFILE } from 'merchant/views/MagicCheckout/ShippingSettings/constants';
import { SettingsWrapper } from 'merchant/views/MagicCheckout/ShippingSettings/styles';
import CreateButton from 'merchant/views/MagicCheckout/common/components/CreateButton';
import MagicDataTable from 'merchant/views/MagicCheckout/common/components/Datatable';
import { openModal, closeModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';

import Modal from './Modal';
import ProductsText from './ProductsText';

const ConfirmationModal = lazy(
  () =>
    import(
      /* webpackChunkName: "MagicShippingZonesSettings" */ 'merchant/views/MagicCheckout/common/components/ConfirmationModal'
    ),
);
const DisplayNotificationTxt = lazy(() =>
  import(
    /* webpackChunkName: "MagicShippingZonesSettings" */ 'merchant/views/MagicCheckout/common/components/ConfirmationModal'
  ).then((module) => ({ default: module.DisplayNotificationTxt })),
);

const ProductCategory = ({
  openModal,
  closeModal,
  deleteCategory: deleteCategoryAction,
  showNotification,
  shippingEngine,
}): JSX.Element => {
  const { selected_profile, shipping_profiles, default_profile } =
    shippingEngine as ShippingEngineStore;
  const isDefault = default_profile?.name === selected_profile;

  const item_category =
    selected_profile && selected_profile !== ADD_PROFILE
      ? shipping_profiles[selected_profile] || {}
      : null;

  const [isModalOpen, setIsModalOpen] = useState(false);

  const deleteCategory = (category) => {
    deleteCategoryAction(category)
      .then(() => {
        showNotification({
          type: 'success',
          message: () => (
            <SuspenseWithLoader type="center">
              <DisplayNotificationTxt notificationTxt="Category deleted successfully" />
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

  const handleEditClick = () => () => {
    setIsModalOpen(true);
  };

  const handleDeleteClick = (item) => () => {
    openModal({
      size: 'small',
      className: `magicToggleConfirmationModal`,
      component: (
        <SuspenseWithLoader type="center">
          <ConfirmationModal
            header="Delete category?"
            desc="Are you sure you want to delete this category"
            affirmativeLabel="Yes"
            abortLabel="No"
            onAffirm={() => deleteCategory(item)}
          />
        </SuspenseWithLoader>
      ),
    });
  };

  const handleCreate = () => {
    setIsModalOpen(true);
  };

  const handleClose = () => {
    setIsModalOpen(false);
    closeModal();
  };

  const Component = () => {
    if (isDefault) {
      return <ProductsText text="All products not in other profiles*" />;
    }
    if (item_category?.name) {
      return (
        <MagicDataTable
          data={[item_category]}
          columns={[CategoryName, ProductCount, actions({ handleDeleteClick, handleEditClick })]}
        />
      );
    }
    return (
      <Box>
        <CreateButton entity="category" onClick={handleCreate} />
      </Box>
    );
  };

  return (
    <Box display="flex" gap="spacing.5" flexDirection={{ base: 'column', l: 'row' }}>
      <Box flex="1">
        <Heading>
          Product categories
          <Text as="span" color="feedback.text.negative.lowContrast">
            *
          </Text>
          <Tooltip
            content="Combine similar products into categories to assign unified shipping rates and rules."
            placement="bottom"
          >
            <TooltipInteractiveWrapper>
              <InfoIcon
                color="surface.text.muted.lowContrast"
                marginLeft="spacing.2"
                position="relative"
                top="spacing.1"
                size="medium"
              />
            </TooltipInteractiveWrapper>
          </Tooltip>
        </Heading>
      </Box>
      <SettingsWrapper>
        <Component />
      </SettingsWrapper>
      {isModalOpen && (
        <Modal categoryId={item_category?.id} isOpen={isModalOpen} closeModal={handleClose} />
      )}
    </Box>
  );
};

const mapStateToProps = (state) => ({
  shippingEngine: state.magicShippingEngine,
});

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      openModal,
      closeModal,
      showNotification,
      deleteCategory,
    },
    dispatch,
  );

export default connect(mapStateToProps, mapDispatchToProps)(ProductCategory);
