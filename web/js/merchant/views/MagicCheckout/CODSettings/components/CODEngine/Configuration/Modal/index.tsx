import React, { useEffect, useState } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

import { Box, Button, CloseIcon, Heading, IconButton } from '@razorpay/blade/components';
import { DisplayNotificationTxt } from 'merchant/views/MagicCheckout/common/components/ConfirmationModal';
import ZoneMapping from './ZoneMapping';
import CategoryMapping from './CategoryMapping';
import {
  ModalBody,
  ModalHeader,
} from 'merchant/views/MagicCheckout/CODSettings/components/CODEngine/Configuration/styled';

import { closeModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';
import {
  fetchConfig,
  mapFeeRulesToZones,
  mapZonesToCategories,
} from 'merchant/reducers/magicCheckout/codEngine/action';

import { MAPPING_TYPES } from 'merchant/views/MagicCheckout/CODSettings/components/CODEngine/Configuration/constants';

const Modal = ({
  type,
  item,
  subText,
  cod_engine_config,
  fetchConfig,
  mapFeeRulesToZones,
  mapZonesToCategories,
  closeModal,
  showNotification,
}) => {
  const MODAL_HEADER = `${item.name} configuration`;
  const isMappingForZones = type === MAPPING_TYPES.ZONE;
  const Component = isMappingForZones ? ZoneMapping : CategoryMapping;
  const { loading } = cod_engine_config;

  const [selectedRuleIds, setSelectedRuleIds] = useState<string[] | Record<string, string[]>>(
    isMappingForZones ? [] : {},
  );

  const handleSave = () => {
    let payload: Record<string, unknown> = {};
    if (isMappingForZones) {
      payload = {
        entity_id: item.id,
        entity_type: 'zone',
        fee_rule_ids: selectedRuleIds,
      };
    } else {
      payload = {
        id: item.id,
        configs: Object.keys(selectedRuleIds)
          .filter((zid) => selectedRuleIds[zid].length)
          .map((zid) => ({
            zone_id: zid,
            fee_rule_ids: selectedRuleIds[zid],
          })),
      };
    }
    const actionFn = isMappingForZones ? mapFeeRulesToZones : mapZonesToCategories;
    actionFn(payload)
      .then(() => {
        return fetchConfig(false); // false to prevent setting edit mode and redirecting back to preview view
      })
      .then(() => {
        showNotification({
          type: 'success',
          message: () => (
            <DisplayNotificationTxt notificationTxt={`${type.label} config saved successfully`} />
          ),
        });
        closeModal();
      })
      .catch((err) => {
        showNotification({
          type: 'error',
          message: err?.errors[0] || 'Something went wrong',
        });
      });
  };

  useEffect(() => {
    if (isMappingForZones && item?.fee_rules?.length) {
      setSelectedRuleIds(item.fee_rules.map((fee) => fee.id));
    } else if (item?.zones?.length) {
      const preSelectedItems = {};
      item?.zones?.forEach((zone) => {
        if (zone.fee_rules?.length) {
          preSelectedItems[zone.id] = zone.fee_rules.map((fee) => fee.id);
        }
      });
      setSelectedRuleIds({ ...preSelectedItems });
    }
  }, []);

  return (
    <div className="cod-config-modal cod-settings-modal" data-testid="slab-modal">
      <ModalHeader>
        <Box>
          <Heading size="medium">{MODAL_HEADER}</Heading>
          {subText()}
        </Box>
        <IconButton
          size="large"
          accessibilityLabel="close-icon"
          icon={CloseIcon}
          onClick={closeModal}
        />
      </ModalHeader>
      <ModalBody>
        <Component selectedRuleIds={selectedRuleIds} setSelectedRuleIds={setSelectedRuleIds} />
      </ModalBody>
      <div className="actions-container">
        <div className="actions-text" />
        <div className="actions">
          <Button onClick={closeModal} isDisabled={loading.mapping} variant="secondary">
            Cancel
          </Button>
          <Button isDisabled={loading.mapping} isLoading={loading.mapping} onClick={handleSave}>
            Save configuration
          </Button>
        </div>
      </div>
    </div>
  );
};

const mapStateToProps = (state) => ({
  cod_engine_config: state.magicCODEngine,
});

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      fetchConfig,
      mapFeeRulesToZones,
      mapZonesToCategories,
      closeModal,
      showNotification,
    },
    dispatch,
  );

export default connect(mapStateToProps, mapDispatchToProps)(Modal);
