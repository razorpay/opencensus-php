import React, { useMemo } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

import { Box, EditComposeIcon, Link, PlusIcon, Text, IconButton } from '@razorpay/blade/components';
import lazy from 'merchant/routes/LazyLoader';
import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';
import DataTable from 'common/ui/Table/DataTable';
import {
  slabRange,
  slatRate,
  zoneName,
} from 'merchant/views/MagicCheckout/CODSettings/components/CODEngine/common/cellItem';

import { ConfigItemWrapper, Seperator, SubText } from './styled';

import { openModal } from 'merchant_common/reducers/modals';
import { MAPPING_TYPES, SERVICEABILITY_TYPES, DEFAULT_CATEGORY_DESCRIPTION } from './constants';

const ConfigurationModal = lazy(
  () => import(/* webpackChunkName: "MagicCODCategoryConfiguration" */ './Modal'),
);

const ConfigItem = ({ type, item, isPreview = false, openModal }) => {
  const isZoneMapping = type === MAPPING_TYPES.ZONE;
  const isCODBlocked = item.type === SERVICEABILITY_TYPES.BLACKLISTED;
  const isEditMode = isZoneMapping ? item?.fee_rules?.length : item?.zones?.length || isCODBlocked;
  const getSubText = (): JSX.Element | string => {
    // hardcoded to india since COD is now supported only in india
    if (isZoneMapping) {
      return (
        <SubText>
          <img src="/dist/css/assets/in-flag.png" alt="in-flag" loading="lazy" />
          <span>India | {item.state_count} states</span>
        </SubText>
      );
    }
    return !item?.is_default ? (
      <SubText>
        <span>Total Items</span>
        <Seperator>|</Seperator>
        <span>{item?.item_count || item?.items.length || 'NA'}</span>
      </SubText>
    ) : (
      DEFAULT_CATEGORY_DESCRIPTION
    );
  };

  const handleClick = () => {
    openModal({
      size: 'medium',
      className: `codSettingModal`,
      component: (
        <SuspenseWithLoader type="center">
          <ConfigurationModal type={type} item={item} subText={getSubText} />
        </SuspenseWithLoader>
      ),
    });
  };

  const TABLE_COLUMS = [slabRange, slatRate];
  if (!isZoneMapping) TABLE_COLUMS.unshift(zoneName);

  const items = useMemo(() => {
    if (isEditMode) {
      return isZoneMapping
        ? item?.fee_rules || []
        : item?.zones
            ?.map((zone) => {
              if (zone.fee_rules) {
                return zone.fee_rules.map((fee, index) => ({
                  ...fee,
                  name: index === 0 ? zone.name : '',
                }));
              }
              return [];
            })
            ?.flat(1);
    }
    return [];
  }, [isEditMode]);

  return (
    <ConfigItemWrapper>
      <Box display="flex" justifyContent="space-between" flex="1">
        <Box>
          <Text size="large">{item?.name}</Text>
          <Text color="surface.text.gray.muted">{getSubText()}</Text>
        </Box>
        {isPreview ? null : (
          <Box>
            {isEditMode ? (
              <IconButton
                onClick={handleClick}
                accessibilityLabel="edit-icon"
                icon={EditComposeIcon}
              />
            ) : (
              <Link variant="button" icon={PlusIcon} onClick={handleClick}>
                Set {type.label.toLowerCase()} configuration
              </Link>
            )}
          </Box>
        )}
      </Box>
      {isEditMode ? (
        !isCODBlocked ? (
          <DataTable customClass="settings-table" items={items} columns={TABLE_COLUMS} />
        ) : (
          <Text weight="semibold" marginTop="10px" color="surface.text.gray.muted">
            COD is blocked
          </Text>
        )
      ) : null}
    </ConfigItemWrapper>
  );
};

const mapStateToProps = (state) => ({
  cod_engine_config: state.magicCODEngine,
});

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      openModal,
    },
    dispatch,
  );

export default connect(mapStateToProps, mapDispatchToProps)(ConfigItem);
