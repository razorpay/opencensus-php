// TODO: Fix the imports, currently out of scope
// @ts-nocheck
import React, { useEffect, useState } from 'react';
import { connect } from 'react-redux';
import { withRouter } from '@libs/web-nexus/common/deprecated/withRouter';
import {
  Box,
  useTheme,
  Spinner,
  Text,
  Link,
  RefreshIcon,
  Card,
  CardBody,
  Button,
} from '@razorpay/blade/components';
import { useBreakpoint } from '@razorpay/blade/utils';
import { AnyAction, Dispatch, bindActionCreators, compose } from 'redux';
import {
  fetchPaymentIdDetails,
  fetchPaymentIdRefundDetails,
  fetchRefundIdDetails,
  fetchInstantRefundFeeFn,
  fetchTransfersFn,
  refundPaymentFn,
  fetchAppDetails,
} from 'apps/self-serve/src/App/Transactions/model';
import { getErrorMessageFromResponse, deepClone } from '@libs/shared-utils';
import RefundModal from 'apps/self-serve/src/App/Transactions/v1/Payments/components/RefundModalNew';
import * as PaymentActions from '@dashboards/payments/reducers/payments/details';
import { useStore } from '@federated/apps/shell/commonStore';
import { isIssueRefundDisabled } from './utils';
import { ErrorWrapper, StyledGoBackBtn } from './styled';
import {
  IPaymentDetails,
  IPaymentIdRefundDetails,
  ICurrentBalance,
  ApplicationDetails,
} from './types';
import PaymentRefundDetails from './PaymentRefundDetails';
import PaymentDetailsSection from './PaymentDetailsSection';
import PaymentDetailsTimeline from './PaymentDetailsTimeline';
import PaymentDetailsOverview from './PaymentDetailsOverview';
import GoBack from 'apps/self-serve/src/App/Transactions/v2/common/components/GoBack';
import ErrorLoadingImage from 'apps/self-serve/src/assets/error-loading.svg';
import {
  trackDetailsClick,
  trackDetailsPageLoad,
} from 'apps/self-serve/src/App/Transactions/v2/common/tracking';
import { RouteComponentProps } from 'apps/self-serve/src/App/Transactions/v2/Payments/types';
import { type PaymentsDashboardUser } from '@libs/shared-types/payments';

const flexDirectionSettings: any = { base: 'column', xl: 'row', l: 'row' };

interface PaymentDetailsProps extends RouteComponentProps<{ id: string }> {
  user: PaymentsDashboardUser;
  fetchCurrentBalance: () => Promise<ICurrentBalance>;
  fetchRefundFee: () => Promise<Record<string, string>>;
}

const PaymentsDetails = (props: PaymentDetailsProps): JSX.Element => {
  const { showNotification, openModal } = useStore((state) => ({
    showNotification: state.showNotification,
    openModal: state.openModal,
  }));
  const {
    user,
    match: { params },
    location,
  } = props;

  const [isLoading, setIsLoading] = useState(true);
  const [paymentIdDetails, setPaymentIdDetails] = useState<IPaymentDetails | null>(null);
  const [applicationDetails, setApplicationDetails] = useState<ApplicationDetails | null>(null);
  const [paymentIdRefundDetails, setPaymentIdRefundDetails] =
    useState<IPaymentIdRefundDetails | null>(null);
  const [error, setError] = useState<string | null | any[]>(null);

  const { theme } = useTheme();
  const { matchedBreakpoint } = useBreakpoint({
    breakpoints: theme.breakpoints,
  });
  const isDesktop = ['xl', 'l'].includes(matchedBreakpoint as string);
  const queryParams = getURLQueryParams(location.search);
  const dashboardFlag: string[] = [];
  if (queryParams.dashboard_flag) {
    dashboardFlag.push(queryParams.dashboard_flag);
  }
  if (user?.isPayerNameEnabled) {
    dashboardFlag.push('upi_payer_name');
  }

  const fetchDetails = async () => {
    setError(null);
    setIsLoading(true);
    const isPaymentsRoute = location.pathname.includes('/payments');
    const { id } = params;
    // slicing the pay_ from the payment id
    const slicedPaymentId = id?.slice(4, id.length + 1);
    try {
      if (isPaymentsRoute) {
        // payments route - api call flow
        const responses = await Promise.all([
          fetchPaymentIdDetails(id, dashboardFlag),
          fetchPaymentIdRefundDetails(id),
        ]);

        setPaymentIdDetails(responses[0].data);
        trackDetailsPageLoad({ latestTransactionStatus: responses[0].data.status });
        setPaymentIdRefundDetails(responses[1].data.items);

        try {
          const appDetailsResponse = await fetchAppDetails(slicedPaymentId);
          setApplicationDetails(appDetailsResponse?.data?.application[0]);
        } catch (err) {
          showNotification({
            type: 'error',
            message: 'Failed to fetch application details',
          });
        }
      } else {
        // refunds route - api call flow
        const refundResponseDetails = await fetchRefundIdDetails(id);
        const paymentId = refundResponseDetails.data.payment_id;
        const responses = await Promise.all([
          fetchPaymentIdDetails(paymentId, dashboardFlag),
          fetchPaymentIdRefundDetails(paymentId),
        ]);
        setPaymentIdDetails(responses[0].data);
        trackDetailsPageLoad({ latestTransactionStatus: responses[0].data.status });
        setPaymentIdRefundDetails(responses[1].data.items);
      }
    } catch ({ errors }: any) {
      const err = getErrorMessageFromResponse(errors);
      setError(err);
      showNotification({
        type: 'error',
        message: 'Something went wrong!!!',
      });
    } finally {
      setIsLoading(false);
    }
  };

  const reFetchPageDetails = async (id: string): Promise<void> => {
    try {
      const responses = await Promise.all([
        fetchPaymentIdDetails(id, dashboardFlag),
        fetchPaymentIdRefundDetails(id),
      ]);
      setPaymentIdDetails(responses[0].data);
      setPaymentIdRefundDetails(responses[1].data.items);
    } catch (error) {
      // empty catch
    }
  };

  useEffect(() => {
    // TODO:find a permanent solution
    // for some reason, page is opening with a downward scroll, adding a temporary solution
    window.scroll({
      top: 0,
      left: 0,
      behavior: 'smooth',
    });
    fetchDetails();
  }, []);

  const onRefundSuccess = () => {
    reFetchPageDetails(paymentIdDetails!.id);
  };

  const openIssueRefundModal = () => {
    const _payment = deepClone(paymentIdDetails);
    _payment.refund = refundPaymentFn(paymentIdDetails!.id);
    _payment.fetchTransfers = fetchTransfersFn(paymentIdDetails!.id);
    _payment.fetchInstantRefundFee = fetchInstantRefundFeeFn;
    trackDetailsClick({
      objectName: 'Issue Refund',
      properties: {
        latestTransactionStatus: _payment.status,
      },
    });

    openModal({
      component: (
        <RefundModal
          fetchMerchantBalance={props.fetchCurrentBalance}
          fetchRefundFee={props.fetchRefundFee}
          payment={_payment}
          onRefund={onRefundSuccess}
        />
      ),
      size: 'small',
    });
  };

  if (isLoading)
    return (
      <Box
        display="flex"
        justifyContent="center"
        alignItems="center"
        flexDirection="column"
        height="90vh"
      >
        <Spinner accessibilityLabel="page-loader" />
      </Box>
    );

  if (error || !paymentIdDetails || !paymentIdRefundDetails)
    return (
      <div className="tabbed-container">
        <GoBack />
        <Card>
          <CardBody>
            <ErrorWrapper>
              <img src={ErrorLoadingImage} alt="error loading data" />
              <Text variant="body" size="medium" weight="regular" color="surface.text.gray.subtle">
                We couldn’t load your details. Refresh to try again
              </Text>
              <Link icon={RefreshIcon} onClick={fetchDetails}>
                Refresh
              </Link>
            </ErrorWrapper>
          </CardBody>
        </Card>
      </div>
    );

  return (
    <div className="tabbed-container" data-testid="payments-details">
      <StyledGoBackBtn>
        <GoBack />
      </StyledGoBackBtn>
      <Box display="flex" gap="spacing.5" flexDirection={flexDirectionSettings}>
        <>
          <Box display="flex" flex="2" gap="spacing.5" flexDirection="column">
            <PaymentDetailsOverview
              paymentDetails={paymentIdDetails}
              paymentIdRefundDetails={paymentIdRefundDetails}
              applicationDetails={applicationDetails}
            />
            {!isDesktop ? (
              <Box>
                <Button
                  isFullWidth
                  variant="secondary"
                  onClick={openIssueRefundModal}
                  isDisabled={isIssueRefundDisabled(paymentIdDetails, props.user)}
                >
                  Issue refund
                </Button>
              </Box>
            ) : null}
            {!isDesktop ? (
              <PaymentDetailsTimeline
                paymentIdDetails={paymentIdDetails}
                paymentIdRefundDetails={paymentIdRefundDetails}
                reFetchPageDetails={reFetchPageDetails}
              />
            ) : null}
            <PaymentDetailsSection
              paymentDetails={paymentIdDetails}
              applicationDetails={applicationDetails}
            />
            <PaymentRefundDetails
              paymentDetails={paymentIdDetails}
              paymentIdRefundDetails={paymentIdRefundDetails}
              reFetchPageDetails={reFetchPageDetails}
            />
          </Box>
          <Box flex="1">
            {isDesktop ? (
              <PaymentDetailsTimeline
                paymentIdDetails={paymentIdDetails}
                paymentIdRefundDetails={paymentIdRefundDetails}
                reFetchPageDetails={reFetchPageDetails}
              />
            ) : null}
          </Box>
        </>
      </Box>
    </div>
  );
};

const mapStateToProps = (state: any) => ({
  user: state.session.user,
});

const mapDispatchToProps = (dispatch: Dispatch<AnyAction>) =>
  bindActionCreators(
    {
      ...PaymentActions,
    },
    dispatch,
  );

export default compose<any>(connect(mapStateToProps, mapDispatchToProps))(
  withRouter(PaymentsDetails),
);
