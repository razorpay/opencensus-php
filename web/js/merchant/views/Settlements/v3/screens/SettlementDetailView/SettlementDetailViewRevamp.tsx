import React, { useEffect } from 'react';
import { Box } from '@razorpay/blade/components';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

import { useSplitzService } from 'common/splitz';
import { analyticsTrackWithUserInfo } from 'common/utils/analytics';
import { selfServeTrackSuccess } from 'common/utils/selfServeAnalytics';
import {
  fetchBreakupDetails as fetchBreakupDetailsAction,
  fetchSettlementConfig,
} from 'merchant/reducers/settlements/details';
import Breakup from 'merchant/views/Settlements/v3/components/Breakup/BreakupRevamp';
import DeductionsEntities from 'merchant/views/Settlements/v3/components/DeductionsEntities/DeductionsEntitiesRevamp';
import GrossSettlementsEntities from 'merchant/views/Settlements/v3/components/GrossSettlementsEntities/GrossSettlementsEntitiesRevamp';
import SettlementInfo from 'merchant/views/Settlements/v3/components/SettlementInfo/SettlementInfoRevamp';
import Timeline from 'merchant/views/Settlements/v3/components/Timeline/TimelineRevamp';
import { SettlementDetailViewInterface } from 'merchant/views/Settlements/v3/typings';
import { getSelfServeSuccessData } from 'merchant/views/Transactions/v1/utils';
import SettlementDetailsOverview from 'merchant/views/Settlements/v3/components/SettlementDetailsOverview';

const SettlementDetailView = ({
  fetchBreakupDetails,
  settlementId,
  breakupDetails,
  settlement,
}: SettlementDetailViewInterface): JSX.Element => {
  const splitz = useSplitzService();
  useEffect(() => {
    fetchBreakupDetails({ id: settlementId });
    fetchSettlementConfig();
  }, [settlementId]);

  useEffect(() => {
    analyticsTrackWithUserInfo({
      objectName: 'Settlements Details Page',
      actionName: 'Rendered',
      screen: 'Settlements',
      properties: {
        page: 'Details View',
        settlements_experiment_name: 'v2',
        settlementId: settlement.id,
        settlementStatus: settlement.status,
        sessionId: window?.session_id ? window.session_id : undefined,
      },
    });

    const selfServeSuccessData = getSelfServeSuccessData(
      'Settlement Details Fetched',
      'Settlement Details',
      splitz,
    );
    selfServeTrackSuccess(selfServeSuccessData);
  }, [settlementId]);

  const shouldShowEntities = (type) => {
    const { loading: isDetailsLoading, items } = breakupDetails;

    if (isDetailsLoading === false && items.length > 0) {
      let entityCount = 0;
      items.forEach((entity) => {
        if (entity.type === type) entityCount++;
      });
      if (entityCount === 0) return false;
      else return true;
    } else {
      return false;
    }
  };

  return (
    <>
      <Box display="flex" gap="spacing.5" flexDirection={{ base: 'column', xl: 'row', l: 'row' }}>
        <Box display="flex" flex="2" gap="spacing.5" flexDirection="column">
          <Box display="flex" gap={{ base: 'spacing.1', m: 'spacing.5' }} flexDirection="column">
            <SettlementDetailsOverview />
            <Breakup />
          </Box>
          <SettlementInfo />
        </Box>
        <Timeline />
      </Box>
      <Box gap="spacing.5" display="flex" flexDirection="column">
        {shouldShowEntities('credit') ? (
          <GrossSettlementsEntities settlementId={settlementId} />
        ) : null}
        {shouldShowEntities('debit') ? <DeductionsEntities settlementId={settlementId} /> : null}
      </Box>
    </>
  );
};

const mapStateToProps = ({ settlement, session }) => ({
  breakupDetails: settlement.breakupDetails,
  settlement: settlement.settlement,
  user: session.user,
});

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    { fetchBreakupDetails: fetchBreakupDetailsAction, fetchSettlementConfig },
    dispatch,
  );

export default connect(mapStateToProps, mapDispatchToProps)(SettlementDetailView);
