import React from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

import ZoneModal from 'merchant/views/MagicCheckout/common/components/ZoneModal';

import {
  createZone,
  updateZone,
  createZoneUpload,
  updateZoneUpload,
} from 'merchant/reducers/magicCheckout/shippingEngine/action';

import { merchantFetch } from 'merchant/utils/ajax';

import { MODAL_MODES } from 'merchant/views/MagicCheckout/common/components/SettingsModal/constants';

import { Zone } from 'merchant/views/MagicCheckout/common/components/ZoneModal/types';
import {
  ModalState,
  ShippingEngineStore,
} from 'merchant/reducers/magicCheckout/shippingEngine/types';
import { useQuery } from '@tanstack/react-query';
import ZonesUpload from './components/ZonesUpload/ZonesUpload';
import { showNotification } from 'merchant_common/reducers/notifications';

interface ModalProps {
  shippingEngine: ShippingEngineStore;
  isOpen: ModalState;
  zoneId?: string;
  closeModal: () => void;
  createZone: (zone: Zone) => Promise<void>;
  updateZone: (zone: Zone) => Promise<void>;
  createZoneUpload: (zone: Zone) => Promise<void>;
  updateZoneUpload: (zone: Zone) => Promise<void>;
  showNotification: (payload: Record<string, unknown>) => void;
}

const Modal = ({
  shippingEngine,
  isOpen,
  zoneId,
  closeModal,
  createZone,
  createZoneUpload,
  updateZone,
  updateZoneUpload,
  showNotification,
}: ModalProps) => {
  const queryKey = Boolean(zoneId) ? [`magic.shipping-zone.${zoneId}`] : [`magic.shipping-zone`]; //queryKey should have only known properties, hence this check else it will throw ts error
  const { isFetching, data: zone } = useQuery({
    queryKey,
    queryFn: (): Promise<Zone> => {
      return merchantFetch({
        url: `1cc/shipping/zones/${zoneId}`,
        method: 'get',
      }).then(({ data }) => data);
    },
    refetchOnWindowFocus: false,
    enabled: Boolean(zoneId),
  });
  const categoryId = shippingEngine.selected_profile
    ? shippingEngine.shipping_profiles[shippingEngine.selected_profile].id
    : undefined;
  return isOpen === 'FILE_UPLOAD' ? (
    <ZonesUpload
      isOpen={!!isOpen}
      closeModal={closeModal}
      zone={zone}
      createZoneUpload={createZoneUpload}
      updateZoneUpload={updateZoneUpload}
      mode={zoneId ? MODAL_MODES.EDIT : MODAL_MODES.CREATE}
      zoneType="shipping"
      showNotification={showNotification}
      itemCategoryId={categoryId}
    />
  ) : (
    <ZoneModal
      isOpen={!!isOpen}
      closeModal={closeModal}
      zone={zone}
      countriesUrl={`1cc/shipping/countries?item_category_id=${categoryId}`}
      createZone={createZone}
      updateZone={updateZone}
      mode={zoneId ? MODAL_MODES.EDIT : MODAL_MODES.CREATE}
      zoneType="shipping"
      loading={shippingEngine.isLoading.zones}
      isZoneFetching={isFetching}
      item_category_id={categoryId}
    />
  );
};

const mapStateToProps = (state) => ({
  shippingEngine: state.magicShippingEngine,
});

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      createZone,
      updateZone,
      showNotification,
      createZoneUpload,
      updateZoneUpload,
    },
    dispatch,
  );

export default connect(mapStateToProps, mapDispatchToProps)(Modal);
