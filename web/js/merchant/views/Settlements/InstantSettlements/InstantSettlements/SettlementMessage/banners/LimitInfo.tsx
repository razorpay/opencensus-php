import React, { useEffect, useMemo, useState } from 'react';
import { Alert, Amount } from '@razorpay/blade/components';
import moment from 'moment';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

import {
  useODSConfig,
  type ODSConfig,
} from 'merchant/views/Settlements/InstantSettlements/query-hooks/useODSConfig';
import {
  getHasMerchantLevelLimit,
  getIsGlobalLimitBreached,
  getIsGlobalLimitBreachedNew,
  getIsOdsMigrationEnabled,
} from 'merchant/views/Settlements/InstantSettlements/utils/common';
import ScheduledModal from 'merchant/views/Settlements/Settlements/components/Modals/ScheduledModal';
import { POST_ENABLE_TYPES } from 'merchant/views/Settlements/Settlements/components/Modals/ScheduledModal/constants';
import {
  setEsBannerSeen,
  getEsBannerSeen,
} from 'merchant/views/Settlements/Settlements/components/Modals/ScheduledModal/utils';
import { openModal as fnOpenModal } from 'merchant_common/reducers/modals';
import {
  useNewODSConfig,
  type NewODSConfig,
} from 'merchant/views/Settlements/InstantSettlements/query-hooks/useNewODSConfig';

const MID_BANNER_LOCALSTAGE_KEY = 'odsMidBannerLastViewed';
const MID_BANNER_EXPIRES = 3; // In Days

/**
 * Renders any one of the following banner at any given time
 *    1. Global Limit Exhausted - has higher priority
 *    2. New Merchant Level Limit Info - this may get deprecated once all merchants are moved to this flow.
 */
const LimitInfo = ({ openModal, user }) => {
  const isOdsExpEnabled = getIsOdsMigrationEnabled(user);
  const odsQuery = isOdsExpEnabled ? useNewODSConfig() : useODSConfig();
  const [lastMidBannerViewedTimeStamp] = useState(() => getEsBannerSeen(MID_BANNER_LOCALSTAGE_KEY));
  const currencyCode = user.merchant.currency || 'INR';
  const isESRestricted = user.isOndemandSettlementsRestricted;

  const shouldShowGlobalLimitBreached = isOdsExpEnabled
    ? getIsGlobalLimitBreachedNew(odsQuery.data as NewODSConfig)
    : getIsGlobalLimitBreached(odsQuery.data as ODSConfig);

  const available_limit = odsQuery?.data?.available_limit;
  const shouldShowMidLimitInfo = useMemo(() => {
    if (
      shouldShowGlobalLimitBreached ||
      isESRestricted ||
      !getHasMerchantLevelLimit(available_limit)
    ) {
      return false;
    }

    if (!lastMidBannerViewedTimeStamp) {
      return true;
    }

    const midBannerExp = moment(lastMidBannerViewedTimeStamp).add(MID_BANNER_EXPIRES, 'days');
    return moment().isSameOrBefore(midBannerExp, 'day');
  }, [
    lastMidBannerViewedTimeStamp,
    odsQuery,
    isESRestricted,
    shouldShowGlobalLimitBreached,
    isOdsExpEnabled,
  ]);

  useEffect(() => {
    if (!lastMidBannerViewedTimeStamp && shouldShowMidLimitInfo) {
      setEsBannerSeen(MID_BANNER_LOCALSTAGE_KEY, moment().startOf('day').toISOString());
    }
  }, [shouldShowMidLimitInfo, lastMidBannerViewedTimeStamp]);

  const handleLearnMore = () => {
    openModal({
      component: (
        <ScheduledModal enabled postModalType={POST_ENABLE_TYPES.ODS_MERCHANT_LEVEL_LIMIT} />
      ),
      size: 'small',
      disableClose: true,
    });
  };

  if (odsQuery.isLoading) {
    return <div data-testid="loading" />;
  }

  const maxLimit = isOdsExpEnabled
    ? (odsQuery.data as NewODSConfig)?.max_limit_per_working_day
    : (odsQuery.data as ODSConfig)?.max_limit;

  const renderBanner = () => {
    if (shouldShowGlobalLimitBreached) {
      return (
        <Alert
          isFullWidth
          color="notice"
          isDismissible={false}
          description="We are temporarily limiting On-Demand Settlements due to exceptionally high usage. We understand the importance of timely settlements and regret any inconvenience this may cause. We expect this to be available the next working day."
        />
      );
    }
    if (shouldShowMidLimitInfo) {
      return (
        <Alert
          isFullWidth
          color="information"
          emphasis="intense"
          isDismissible={false}
          description={
            <>
              Instant Settlements now come with a daily settlement limit. You will be able to
              withdraw only upto{' '}
              <Amount
                size="small"
                color="surface.text.staticWhite.normal"
                currency={currencyCode}
                value={(maxLimit || 0) / 100}
                suffix="humanize"
              />{' '}
              today.
            </>
          }
          actions={{
            secondary: {
              text: 'Learn More',
              onClick: handleLearnMore,
            },
          }}
        />
      );
    }
    return null;
  };

  return renderBanner();
};

const mapStateToProps = (state) => {
  return {
    user: state.session.user,
  };
};

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators(
    {
      openModal: fnOpenModal,
    },
    dispatch,
  );
};

const LimitWrapper = connect(mapStateToProps, mapDispatchToProps)(LimitInfo);

export { LimitWrapper as LimitInfo };
