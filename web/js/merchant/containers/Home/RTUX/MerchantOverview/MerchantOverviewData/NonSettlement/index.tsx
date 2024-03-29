import React, { useEffect, useMemo } from 'react';
import { Text, Box, Heading } from '@razorpay/blade/components';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

import {
  IAnalyticsProperties,
  INonSettlement,
} from 'merchant/containers/Home/RTUX/MerchantOverview/types';
import {
  fetchSchedule as fetchScheduleFn,
  fetchHolidayList as fetchHolidayListFn,
  fetchSettlementConfig as fetchSettlementConfigFn,
} from 'merchant/reducers/settlements/details';
import { openModal as fnOpenModal } from 'merchant_common/reducers/modals';

import { getNonSettlementCardContent } from './utils';
import { Image } from 'merchant/containers/Home/RTUX/MerchantOverview/styled';

const NonSettlement = ({
  fetchSettlementConfig,
  fetchSchedule,
  fetchHolidayList,
  openModal,
  is_transacted,
  settlement_schedule,
  analyticsProperties,
}: INonSettlement & IAnalyticsProperties): JSX.Element => {
  useEffect(() => {
    fetchSettlementConfig();
    fetchSchedule();
    fetchHolidayList();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  const { title, subtitle, action, illustration } = useMemo(
    () =>
      getNonSettlementCardContent({
        is_transacted,
        settlement_schedule,
        openModal,
        analyticsProperties,
      }),
    [is_transacted, settlement_schedule, openModal],
  );

  return (
    <Box
      display="flex"
      gap="spacing.7"
      flexDirection={{ base: 'column', l: 'row' }}
      justifyContent={{ base: 'center', l: 'unset' }}
      alignItems={{ base: 'center', l: 'unset' }}
    >
      <Image src={`/img/rtux/${illustration}`} />
      <Box display="flex" flexDirection="column" justifyContent="space-between" gap="spacing.5">
        <Box textAlign={{ base: 'center', l: 'unset' }}>
          <Heading size="small">{title}</Heading>
          <Text size="medium" color="surface.text.gray.muted">
            {subtitle}
          </Text>
        </Box>
        <Box
          display="flex"
          gap="spacing.5"
          flexDirection={{ base: 'column', l: 'row' }}
          alignItems="center"
        >
          {action}
        </Box>
      </Box>
    </Box>
  );
};

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators(
    {
      openModal: fnOpenModal,
      fetchHolidayList: fetchHolidayListFn,
      fetchSchedule: fetchScheduleFn,
      fetchSettlementConfig: fetchSettlementConfigFn,
    },
    dispatch,
  );
};
export default connect(null, mapDispatchToProps)(NonSettlement);
