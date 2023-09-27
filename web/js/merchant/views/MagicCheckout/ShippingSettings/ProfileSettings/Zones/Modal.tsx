import React from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

import ZoneModal from 'merchant/views/MagicCheckout/common/components/ZoneModal';

import { createZone, updateZone } from 'merchant/reducers/magicCheckout/shippingEngine/action';

import { merchantFetch } from 'merchant/utils/ajax';

import { MODAL_MODES } from 'merchant/views/MagicCheckout/common/components/SettingsModal/constants';

import { Zone } from 'merchant/views/MagicCheckout/common/components/ZoneModal/types';
import { ShippingEngineStore } from 'merchant/reducers/magicCheckout/shippingEngine/types';
import { useQuery } from 'react-query';

interface ModalProps {
  shippingEngine: ShippingEngineStore;
  isOpen: boolean;
  zoneId?: string;
  closeModal: () => void;
  createZone: (zone: Zone) => Promise<void>;
  updateZone: (zone: Zone) => Promise<void>;
}

const Modal = ({
  shippingEngine,
  isOpen,
  zoneId,
  closeModal,
  createZone,
  updateZone,
}: ModalProps) => {
  const { isFetching, data: zone } = useQuery(
    [`magic.shipping-zone.${zoneId}`],
    (): Promise<Zone> => {
      return merchantFetch({
        url: `1cc/shipping/zones/${zoneId}`,
        method: 'get',
      }).then(({ data }) => data);
    },
    {
      refetchOnWindowFocus: false,
      enabled: zoneId,
    },
  );
  const categoryId = shippingEngine.selected_profile
    ? shippingEngine.shipping_profiles[shippingEngine.selected_profile].id
    : undefined;

  return (
    <ZoneModal
      isOpen={isOpen}
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
    },
    dispatch,
  );

export default connect(mapStateToProps, mapDispatchToProps)(Modal);
