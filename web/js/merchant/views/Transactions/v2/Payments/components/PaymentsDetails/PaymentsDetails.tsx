import React, { useEffect, useState } from 'react';
import { connect } from 'react-redux';
import GoBack from 'merchant/views/Transactions/v2/common/components/GoBack';
import { RouteComponentProps, withRouter } from 'react-router-dom';
import PaymentDetailsOverview from './PaymentDetailsOverview';
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
import PaymentDetailsTimeline from './PaymentDetailsTimeline';
import PaymentDetailsSection from './PaymentDetailsSection';
import PaymentRefundDetails from './PaymentRefundDetails';
import { useBreakpoint } from '@razorpay/blade/utils';
import { bindActionCreators, compose } from 'redux';
import {
  fetchPaymentIdDetails,
  fetchPaymentIdRefundDetails,
  fetchRefundIdDetails,
  fetchInstantRefundFeeFn,
  fetchTransfersFn,
  refundPaymentFn,
  fetchAppDetails,
} from 'merchant/views/Transactions/model';
import { showNotification } from 'merchant_common/reducers/notifications';
import {
  IPaymentDetails,
  IPaymentIdRefundDetails,
  ICurrentBalance,
  ApplicationDetails,
} from './types';
import { getErrorMessageFromResponse, deepClone } from 'common/utils/rzp-utils';
import ErrorLoadingImage from 'assets/transactions/error-loading.svg';
import { ErrorWrapper, StyledGoBackBtn } from './styled';
import { isIssueRefundDisabled } from './utils';
import RefundModal from 'merchant/views/Transactions/v1/Payments/components/RefundModalNew';
import * as ModalActions from 'merchant_common/reducers/modals';
import * as PaymentActions from 'merchant/reducers/payments/details';
import {
  trackDetailsClick,
  trackDetailsPageLoad,
} from 'merchant/views/Transactions/v2/common/tracking';

const flexDirectionSettings: any = { base: 'column', xl: 'row', l: 'row' };

interface PaymentDetailsProps extends RouteComponentProps<{ id: string }> {
  showNotification: (data: any) => void;
  user: Record<string, string>;
  openModal: (args) => void;
  fetchCurrentBalance: () => Promise<ICurrentBalance>;
  fetchRefundFee: () => Promise<Record<string, string>>;
}

const PaymentsDetails = (props: PaymentDetailsProps): JSX.Element => {
  const {
    match: { params },
    location,
    showNotification,
  } = props;

  const [isLoading, setIsLoading] = useState(true);
  const [paymentIdDetails, setPaymentIdDetails] = useState<IPaymentDetails | null>(null);
  const [applicationDetails, setApplicationDetails] = useState<ApplicationDetails | null>(null);
  const [paymentIdRefundDetails, setPaymentIdRefundDetails] =
    useState<IPaymentIdRefundDetails | null>(null);
  const [error, setError] = useState(null);

  const { theme } = useTheme();
  const { matchedBreakpoint } = useBreakpoint({
    breakpoints: theme.breakpoints,
  });
  const isDesktop = ['xl', 'l'].includes(matchedBreakpoint as string);

  const fetchDetails = async () => {
    setError(null);
    setIsLoading(true);
    const isPaymentsRoute = location.pathname.includes('/payments');
    const { id } = params;
    // slicing the pay_ from the payment id
    const slicedPaymentId = id.slice(4, id.length + 1);
    try {
      if (isPaymentsRoute) {
        // payments route - api call flow
        const responses = await Promise.all([
          fetchPaymentIdDetails(id),
          fetchPaymentIdRefundDetails(id),
          fetchAppDetails(slicedPaymentId),
        ]);
        setPaymentIdDetails(responses[0].data);
        trackDetailsPageLoad({ latestTransactionStatus: responses[0].data.status });
        setPaymentIdRefundDetails(responses[1].data.items);
        setApplicationDetails(responses[2]?.data?.application[0]);
      } else {
        // refunds route - api call flow
        const refundResponseDetails = await fetchRefundIdDetails(id);
        const paymentId = refundResponseDetails.data.payment_id;
        const responses = await Promise.all([
          fetchPaymentIdDetails(paymentId),
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
        fetchPaymentIdDetails(id),
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
    const { openModal } = props;
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
              <Text type="subtle" variant="body" size="medium" weight="regular" contrast="low">
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
            {!isDesktop && (
              <PaymentDetailsTimeline
                paymentIdDetails={paymentIdDetails}
                paymentIdRefundDetails={paymentIdRefundDetails}
                reFetchPageDetails={reFetchPageDetails}
              />
            )}
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
            {isDesktop && (
              <PaymentDetailsTimeline
                paymentIdDetails={paymentIdDetails}
                paymentIdRefundDetails={paymentIdRefundDetails}
                reFetchPageDetails={reFetchPageDetails}
              />
            )}
          </Box>
        </>
      </Box>
    </div>
  );
};

const mapStateToProps = (state) => ({
  user: state.session.user,
});

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      ...PaymentActions,
      ...ModalActions,
      showNotification,
    },
    dispatch,
  );

export default compose<any>(
  withRouter,
  connect(mapStateToProps, mapDispatchToProps),
)(PaymentsDetails);
