import React, { Suspense } from 'react';
import { connect } from 'react-redux';
import { openModal as openModalReducer } from 'merchant_common/reducers/modals';
import {
  clickHistoryCreditsGA,
  CLICK_ADD_FEE_CREDITS,
  CLICK_ADD_REFUND_CREDITS,
  FEE_CREDITS_ADDED,
  FEE_CREDITS_FAILED,
  REFUND_CREDITS_ADDED,
  REFUND_CREDITS_FAILED,
  getAnalyticsData,
} from 'merchant/views/Account/Credits/ga';
import { showNotification as showNotificationReducer } from 'merchant_common/reducers/notifications';
import { fetchCreditBalance as fetchCreditBalanceReducer } from 'merchant/reducers/credits';
import { analyticsTrack } from 'common/utils/analytics';
import { bindActionCreators } from 'redux';
import lazy from 'merchant/routes/LazyLoader';
import Popover, { PopoverBody } from 'common/ui/Popover';
import rolesList from 'merchant/helpers/permissions/roles-list';
import Loader from 'common/ui/Loader';
import { selfServeTrackInitiate, selfServeTrackSuccess } from 'common/utils/selfServeAnalytics';
import {
  Amount,
  ArrowRightIcon,
  Box,
  Button,
  Card,
  CardBody,
  CardHeader,
  CardHeaderLeading,
  CardHeaderTrailing,
  CardHeaderLink,
} from '@razorpay/blade/components';
import { i18nifyConvertToMajorUnit } from 'merchant/views/Transactions/v2/common/utils';
import WithdrawButton from 'merchant/views/Account/components/WithdrawModal/WithdrawButton';

const ViewCreditHistoryTable = lazy(() =>
  import(
    /* webpackChunkName: 'ViewCreditHistoryTable' */ 'merchant/views/Account/Credits/components/ViewCreditHistoryTable'
  ),
);

const AddCredits = lazy(() =>
  import(
    /* webpackChunkName: 'AddCredits' */ 'merchant/views/Account/Credits/components/AddCredits'
  ),
);

function CreditDetails({
  title,
  description,
  openModal,
  showNotification,
  user,
  type,
  setStatus,
  totalCredits,
  fetchCreditBalance,
  creditItems,
  mode,
}) {
  const addCredits = (transaction) => {
    return new Promise((resolve, reject) => {
      if (transaction.razorpay_payment_id) {
        resolve(true);
      } else {
        reject('Payment failed');
      }
    })
      .then((_) => {
        fetchCreditBalance();
        showNotification({
          type: 'success',
          message: 'Credits added successfully',
        });
        selfServeTrackSuccess(getAnalyticsData('add', type));
        setTimeout(() => {
          showNotification({
            type: 'success',
            message: 'Credits might take sometime to reflect. Please check in few minutes.',
          });
        }, 2000);

        type === 'fee' && analyticsTrack(FEE_CREDITS_ADDED);
        type === 'refund' && analyticsTrack(REFUND_CREDITS_ADDED);
      })
      .catch((error) => {
        setStatus({
          type: 'error',
          message: error,
        });
        type === 'fee' && analyticsTrack(FEE_CREDITS_FAILED);
        type === 'refund' && analyticsTrack(REFUND_CREDITS_FAILED);
      });
  };

  const statusHandler = ({ errors }) => {
    showNotification({
      type: 'error',
      message: `${errors}`,
    });
  };

  const addCreditsHandler = () => {
    const analyticsData =
      title === 'Fee Credits' ? CLICK_ADD_FEE_CREDITS : CLICK_ADD_REFUND_CREDITS;
    analyticsTrack(analyticsData);
    selfServeTrackInitiate(getAnalyticsData('add', type));
    openModal({
      size: 'small',
      component: (
        <Suspense fallback={<Loader />}>
          <AddCredits
            type={type}
            addHandler={addCredits}
            user={user}
            statusHandler={statusHandler}
          />
        </Suspense>
      ),
    });
  };

  const handleViewHistory = () => {
    selfServeTrackInitiate(getAnalyticsData('view', type));
    openModal({
      size: 'large',
      component: (
        <Suspense fallback={<Loader />}>
          <ViewCreditHistoryTable creditItems={creditItems} title={title} type={type} />
        </Suspense>
      ),
    });
    analyticsTrack(clickHistoryCreditsGA(title));
  };

  const showSelfServeButtons =
    [rolesList.OWNER, rolesList.ADMIN].includes(user.role) && !user.isCreditSelfServeDisabled;

  return (
    <Card>
      <CardHeader>
        <CardHeaderLeading title={title} subtitle={description} />
        <CardHeaderTrailing
          visual={
            <CardHeaderLink
              variant="button"
              onClick={handleViewHistory}
              icon={ArrowRightIcon}
              iconPosition="right"
            >
              View History
            </CardHeaderLink>
          }
        />
      </CardHeader>
      <CardBody>
        <Box
          gap="spacing.4"
          display="flex"
          justifyContent="space-between"
          flexDirection={{ base: 'column', l: 'row' }}
        >
          <Amount
            size="large"
            type="heading"
            weight="semibold"
            value={i18nifyConvertToMajorUnit(totalCredits, user.merchant.currency)}
            currency={user.merchant.currency}
          />
          {showSelfServeButtons ? (
            <Box display="flex" flexWrap="wrap" gap="spacing.3">
              <WithdrawButton
                title={title}
                credits={totalCredits}
                type={type}
                submitHandler={fetchCreditBalance}
              />
              <Box width={{ base: '100%', s: '185px' }}>
                <Button
                  isFullWidth
                  variant="secondary"
                  isDisabled={mode !== 'live'}
                  onClick={() => addCreditsHandler(type)}
                >
                  Add {title}
                </Button>
                {mode !== 'live' ? (
                  <Popover align="bottom" theme="dark">
                    <PopoverBody>
                      You cannot add {type} credits in test mode. Switch to live mode to add
                      credits.
                    </PopoverBody>
                  </Popover>
                ) : null}
              </Box>
            </Box>
          ) : null}
        </Box>
      </CardBody>
    </Card>
  );
}

const mapStateToProps = (state) => ({
  ...state.session,
});

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators(
    {
      openModal: openModalReducer,
      showNotification: showNotificationReducer,
      fetchCreditBalance: fetchCreditBalanceReducer,
    },
    dispatch,
  );
};

export default connect(mapStateToProps, mapDispatchToProps)(CreditDetails);
