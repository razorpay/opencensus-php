import React, { useEffect } from 'react';
import { Alert, Box } from '@razorpay/blade/components';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

import { useSplitzService } from 'common/splitz';
import { analyticsTrackWithUserInfo } from 'common/utils/analytics';
import { selfServeTrackSuccess } from 'common/utils/selfServeAnalytics';
import { fetchBreakupDetails as fetchBreakupDetailsAction } from 'merchant/reducers/settlements/details';
import Breakup from 'merchant/views/Settlements/v3/components/Breakup';
import DeductionsEntities from 'merchant/views/Settlements/v3/components/DeductionsEntities/DeductionsEntities';
import FAQs from 'merchant/views/Settlements/v3/components/FAQs';
import GrossSettlementsEntities from 'merchant/views/Settlements/v3/components/GrossSettlementsEntities/GrossSettlementsEntities';
import SettlementInfo from 'merchant/views/Settlements/v3/components/SettlementInfo';
import Timeline from 'merchant/views/Settlements/v3/components/Timeline';
import { SettlementDetailViewInterface } from 'merchant/views/Settlements/v3/typings';
import { getFailedAlert } from 'merchant/views/Settlements/v3/utils/settlementInfo';
import { getSelfServeSuccessData } from 'merchant/views/Transactions/v1/utils';

const SettlementDetailView = ({
  fetchBreakupDetails,
  settlementId,
  breakupDetails,
  settlement,
}: SettlementDetailViewInterface): JSX.Element => {
  const splitz = useSplitzService();
  useEffect(() => {
    fetchBreakupDetails({ id: settlementId });
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

  const { shouldShouldFailedAlert, bannerConfig } = getFailedAlert({ settlement });

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
      {shouldShouldFailedAlert && (
        <Alert
          {...(bannerConfig?.action ? bannerConfig.action(settlement) : {})}
          contrast="low"
          description={bannerConfig?.description || ''}
          intent="negative"
          isDismissible={false}
          title={bannerConfig?.heading}
          isFullWidth
        />
      )}
      <SettlementInfo settlementId={settlementId} />
      <Box
        display="flex"
        flexWrap="wrap"
        gap="spacing.5"
        flexDirection={{ base: 'column', m: 'row' }}
      >
        <Breakup />
        <Timeline />
      </Box>
      {shouldShowEntities('credit') ? (
        <GrossSettlementsEntities settlementId={settlementId} />
      ) : null}
      {shouldShowEntities('debit') ? <DeductionsEntities settlementId={settlementId} /> : null}
      <FAQs />
    </>
  );
};

const mapStateToProps = ({ settlement, session }) => ({
  breakupDetails: settlement.breakupDetails,
  settlement: settlement.settlement,
  user: session.user,
});

const mapDispatchToProps = (dispatch) =>
  bindActionCreators({ fetchBreakupDetails: fetchBreakupDetailsAction }, dispatch);

export default connect(mapStateToProps, mapDispatchToProps)(SettlementDetailView);
