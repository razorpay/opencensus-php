import React, { useEffect } from 'react';
import { connect } from 'react-redux';
import { AnyAction, Dispatch, bindActionCreators } from 'redux';

import { Heading, Text } from '@razorpay/blade/components';
import Spinner from 'common/ui/Spinner';
import ErrorBoundary, { Ranks, Teams } from 'common/new-ui/ErrorBoundary';
import {
  fetchPartialCODConfigs,
  resetPartialCODConfigs,
  updatePartialCODConfigs,
} from 'merchant/reducers/magicCheckout/partialCOD/actions';
import { showNotification as displayNotification } from 'merchant_common/reducers/notifications';
import PartialCODEditConfigs from 'merchant/views/MagicCheckout/PartialCOD/components/PartialCODEditConfigs';
import { PartialCODWrapper } from 'merchant/views/MagicCheckout/PartialCOD/styled';
import { PartialCODContextProvider } from 'merchant/views/MagicCheckout/PartialCOD/context/PartialCODContext';

const PartialCOD = ({
  configs,
  fetchConfigs,
  platform,
  shopId,
  showNotification,
  updateConfigs,
}) => {
  const { error, isLoading, partialCODConfigs, isPartialCODEnabled } = configs;

  useEffect(() => {
    if (fetchConfigs && !Object.entries(partialCODConfigs).length) {
      fetchConfigs();
    }
  }, [fetchConfigs]);

  if (isLoading) {
    return (
      <div className="spinner-container">
        <Spinner center />
      </div>
    );
  }

  return (
    <ErrorBoundary team={Teams?.MAGIC_CHECKOUT} rank={Ranks.P0} resetOnProps>
      <PartialCODWrapper>
        <div className="header-container">
          <Heading size="large">Partial COD</Heading>
          <Text size="medium" variant="body" weight="regular" color="surface.text.gray.muted">
            Let customers pay a partial amount online before cash on delivery
          </Text>
        </div>
        <PartialCODContextProvider
          isPartialCODEnabled={isPartialCODEnabled}
          partialCODConfigs={partialCODConfigs}
          shopId={shopId}
          platform={platform}
          updateConfigs={updateConfigs}
          showNotification={showNotification}
        >
          <PartialCODEditConfigs />
        </PartialCODContextProvider>
      </PartialCODWrapper>
    </ErrorBoundary>
  );
};

const mapStateToProps = (state: any) => ({
  configs: state.magicPartialCODConfigs,
  platform: state.magic_settings?.platform,
  shopId: state.magic_settings?.shop_id,
});

const mapDispatchToProps = (dispatch: Dispatch<AnyAction>) =>
  bindActionCreators(
    {
      fetchConfigs: fetchPartialCODConfigs,
      showNotification: displayNotification,
      resetConfigs: resetPartialCODConfigs,
      updateConfigs: updatePartialCODConfigs,
    },
    dispatch,
  );

export default connect(mapStateToProps, mapDispatchToProps)(PartialCOD);
