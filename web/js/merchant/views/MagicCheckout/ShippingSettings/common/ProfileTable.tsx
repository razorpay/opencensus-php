import React, { useMemo } from 'react';
import { Text } from '@razorpay/blade/components';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import styled from 'styled-components';

import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';
import { setProfile, deleteCategory } from 'merchant/reducers/magicCheckout/shippingEngine/action';
import { ShippingProfile } from 'merchant/reducers/magicCheckout/shippingEngine/types';
import lazy from 'merchant/routes/LazyLoader';
import {
  ADD_PROFILE,
  PROFILE_TYPES,
} from 'merchant/views/MagicCheckout/ShippingSettings/constants';
import { useShippingSettingsRouteContext } from 'merchant/views/MagicCheckout/ShippingSettings/context/RouteContext';
import MagicDataTable from 'merchant/views/MagicCheckout/common/components/Datatable';
import { ColumnDef } from 'merchant/views/MagicCheckout/common/components/Datatable/types';
import { openModal, closeModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';

import { Profile, ShippingMethods, Zones, actions } from './cellItem';

const ConfirmationModal = lazy(
  () =>
    import(
      /* webpackChunkName: "MagicShippingEngineProfileTable" */ 'merchant/views/MagicCheckout/common/components/ConfirmationModal'
    ),
);
const DisplayNotificationTxt = lazy(() =>
  import(
    /* webpackChunkName: "MagicShippingEngineProfileTable" */ 'merchant/views/MagicCheckout/common/components/ConfirmationModal'
  ).then((module) => ({ default: module.DisplayNotificationTxt })),
);

const EmptyText = styled.p(
  ({ theme }) => `
  padding: ${theme.spacing[5]}px 0;
  border-style: solid;
  border: 1px solid ${theme.colors.surface.border.gray.muted};
  border-top: none;
  border-bottom-left-radius: ${theme.border.radius.small}px;
  border-bottom-right-radius: ${theme.border.radius.small}px;
  text-align: center;
  margin-top: -1px
`,
);

const EmptyComponent = ({ type = 'default' }) => (
  <EmptyText>
    <Text size="small" color="surface.text.gray.muted">
      No {type} shipping {type === PROFILE_TYPES.DEFAULT ? 'profile' : 'profiles'} configured
    </Text>
  </EmptyText>
);

const ProfileTable = ({
  type = PROFILE_TYPES.DEFAULT,
  shipping_profiles,
  default_profile,
  openModal,
  closeModal,
  deleteCategory: deleteCategoryAction,
  showNotification,
  setProfile,
}): JSX.Element => {
  const { setActiveRoute } = useShippingSettingsRouteContext();

  const handleEditClick = (item) => () => {
    setActiveRoute('profile');
    setProfile(item.name);
  };

  const deleteCategory = (item) => {
    deleteCategoryAction(item)
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

  const tableData = useMemo(() => {
    const data: ShippingProfile[] = [];
    if (Object.keys(shipping_profiles).length) {
      if (type === PROFILE_TYPES.DEFAULT) {
        return [shipping_profiles[default_profile?.name]];
      } else {
        Object.keys(shipping_profiles).forEach((profile) => {
          if (profile !== ADD_PROFILE && profile !== default_profile?.name) {
            data.push(shipping_profiles[profile]);
          }
        });
        return data;
      }
    }
    return [];
  }, [type, shipping_profiles]);

  const isDefaultProfileType = type === PROFILE_TYPES.DEFAULT;

  const COLUMNS: ColumnDef<ShippingProfile>[] = [Profile, Zones, ShippingMethods];
  if (type === PROFILE_TYPES.GENERAL) {
    COLUMNS.push(actions({ handleDeleteClick, handleEditClick }));
  }

  let gridTemplateColumns = 'repeat(3, 2fr)';
  if (!isDefaultProfileType) {
    gridTemplateColumns += ' 1fr';
  }

  return (
    <MagicDataTable
      EmptyComponent={() => <EmptyComponent type={type} />}
      columns={COLUMNS}
      data={tableData}
      gridTemplateColumns={gridTemplateColumns}
    />
  );
};

const mapStateToProps = (state) => ({
  shipping_profiles: state.magicShippingEngine.shipping_profiles,
  default_profile: state.magicShippingEngine.default_profile,
});

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      openModal,
      closeModal,
      deleteCategory,
      showNotification,
      setProfile,
    },
    dispatch,
  );

export default connect(mapStateToProps, mapDispatchToProps)(ProfileTable);
