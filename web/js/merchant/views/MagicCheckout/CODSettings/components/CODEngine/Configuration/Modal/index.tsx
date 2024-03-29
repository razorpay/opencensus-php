import React, { useEffect, useState } from 'react';
import { Box, Button, CloseIcon, Heading, IconButton } from '@razorpay/blade/components';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

import {
  fetchConfig,
  mapFeeRulesToZones,
  mapZonesToCategories,
} from 'merchant/reducers/magicCheckout/codEngine/action';
import {
  MAPPING_TYPES,
  SERVICEABILITY_TYPES,
} from 'merchant/views/MagicCheckout/CODSettings/components/CODEngine/Configuration/constants';
import {
  ModalBody,
  ModalHeader,
} from 'merchant/views/MagicCheckout/CODSettings/components/CODEngine/Configuration/styled';
import { DisplayNotificationTxt } from 'merchant/views/MagicCheckout/common/components/ConfirmationModal';
import { MAGIC_APP_NAME, RCOD_APP_NAME } from 'merchant/views/MagicCheckout/common/constants';
import { closeModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';

import CategoryMapping from './CategoryMapping';
import ZoneMapping from './ZoneMapping';

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
  isRCOD,
}) => {
  const MODAL_HEADER = `${item.name} configuration`;
  const isMappingForZones = type === MAPPING_TYPES.ZONE;
  const Component = isMappingForZones ? ZoneMapping : CategoryMapping;
  const { loading } = cod_engine_config;

  const [selectedRuleIds, setSelectedRuleIds] = useState<string[] | Record<string, string[]>>(
    isMappingForZones ? [] : {},
  );

  const [isCODBlocked, setIsCODBlocked] = useState<boolean>(
    item?.type === SERVICEABILITY_TYPES.BLACKLISTED,
  );

  const getCategoryConfigsPayload = () => {
    if (isCODBlocked) {
      return {
        id: item.id,
        type: SERVICEABILITY_TYPES.BLACKLISTED,
      };
    }

    return {
      id: item.id,
      type: SERVICEABILITY_TYPES.SERVICEABLE,
      configs: Object.keys(selectedRuleIds)
        .filter((zid) => selectedRuleIds[zid].length)
        .map((zid) => ({
          zone_id: zid,
          fee_rule_ids: selectedRuleIds[zid],
        })),
    };
  };

  const handleSave = () => {
    let payload: Record<string, unknown> = {};
    if (isMappingForZones) {
      payload = {
        entity_id: item.id,
        entity_type: 'zone',
        fee_rule_ids: selectedRuleIds,
      };
    } else {
      payload = getCategoryConfigsPayload();
    }
    const actionFn = isMappingForZones ? mapFeeRulesToZones : mapZonesToCategories;
    actionFn(payload)
      .then(() => {
        return fetchConfig(false, isRCOD ? RCOD_APP_NAME : MAGIC_APP_NAME); // false to prevent setting edit mode and redirecting back to preview view
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
          <Heading size="small">{MODAL_HEADER}</Heading>
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
        <Component
          selectedRuleIds={selectedRuleIds}
          setSelectedRuleIds={setSelectedRuleIds}
          isCODBlocked={isCODBlocked}
          setIsCODBlocked={setIsCODBlocked}
        />
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
  isRCOD: state.magic_settings.rcodEnabled,
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
