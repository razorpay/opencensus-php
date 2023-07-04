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

import { merchantFetch } from 'merchant/utils/ajax';

import { POPOVER_CONTENT, MODAL_MODES } from 'merchant/views/MagicCheckout/CODSettings/constants';

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
}) {
  const [countries, setCountries] = useState([]);
  const [errorText, setErrorText] = useState('');
  useEffect(() => {
    merchantFetch({
      url: '1cc/shipping/cod/countries',
      method: 'get',
    }).then(({ data }) => {
      const countries =
        data?.countries?.map((c) => ({ ...c, total_states: c.states.length })) || [];
      setCountries(countries);
    });
  }, []);

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
          <ZoneModal mode={mode} id={id} allcountries={countries} />
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

  const onEditClick = (id) => () => {
    openZoneModal(MODAL_MODES.EDIT, id);
  };

  return (
    <div className="cod-setting-item">
      <SettingsLabel
        value="COD zones"
        required
        popoverContent={POPOVER_CONTENT.zones}
        errorText={errorText}
      />
      <i className="i i-line" />
      <div className="cod-options-container">
        {zones.length === 0 ? (
          <button onClick={openZoneModal} className="add-zone-button">
            + Add zones
          </button>
        ) : (
          <div className="cod-table-wrapper">
            <DataTable
              customClass="settings-table"
              items={zones}
              columns={[zoneName, zoneCountry, zoneStates, actions({ onEditClick, onDeleteClick })]}
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
    },
    dispatch,
  );

export default connect(mapStateToProps, mapDispatchToProps)(ZoneSettings);
