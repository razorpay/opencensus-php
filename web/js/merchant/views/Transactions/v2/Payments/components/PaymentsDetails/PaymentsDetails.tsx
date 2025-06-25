import React, { useEffect, useState } from 'react';
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
import ErrorLoadingImage from 'assets/transactions/error-loading.svg';
import isEmpty from 'lodash/isEmpty';
import { connect } from 'react-redux';
import { useNavigate } from 'react-router-dom';
import { bindActionCreators, compose } from 'redux';

import { RouteComponentProps } from 'common/deprecated/RouteComponentProps';
import { withRouter } from 'common/deprecated/withRouter';
import { useI18Service } from 'common/i18';
import { useSplitzService } from 'common/splitz';
import User from 'common/typings/User';
import { getErrorMessageFromResponse, deepClone, getURLQueryParams } from 'common/utils/rzp-utils';
import { fetchTerminalProviders } from 'merchant/reducers/navigator/details';
import * as PaymentActions from 'merchant/reducers/payments/details';
import {
  fetchPaymentIdDetails,
  fetchPaymentIdRefundDetails,
  fetchRefundIdDetails,
  fetchInstantRefundFeeFn,
  fetchTransfersFn,
  refundPaymentFn,
  fetchAppDetails,
  fetchRefundConfig,
} from 'merchant/views/Transactions/model';
import RefundModal from 'merchant/views/Transactions/v1/Payments/components/RefundModalNew';
import RefundModalRevamp from 'merchant/views/Transactions/v2/Payments/components/PaymentRefund';
import GoBack from 'merchant/views/Transactions/v2/common/components/GoBack';
import {
  trackDetailsClick,
  trackDetailsPageLoad,
} from 'merchant/views/Transactions/v2/common/tracking';
import {
  isRefundRevampEnabled,
  isRefundConfigRevampEnabled,
  isUnregisteredWebsiteErrorEnabled,
} from 'merchant/views/Transactions/v2/common/utils';
import * as ModalActions from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';

import PaymentDetailsOverview from './PaymentDetailsOverview';
import PaymentDetailsSection from './PaymentDetailsSection';
import PaymentDetailsTimeline from './PaymentDetailsTimeline';
import PaymentRefundDetails from './PaymentRefundDetails';
import { ErrorWrapper, StyledGoBackBtn } from './styled';
import {
  IPaymentDetails,
  IPaymentIdRefundDetails,
  ICurrentBalance,
  ApplicationDetails,
} from './types';
import { isIssueRefundDisabled, isIssueRefundBtnHidden } from './utils';

const flexDirectionSettings: any = { base: 'column', xl: 'row', l: 'row' };

interface PaymentDetailsProps extends RouteComponentProps<{ id: string }> {
  showNotification: (data: any) => void;
  user: User;
  org: any;
  openModal: (args) => void;
  fetchCurrentBalance: () => Promise<ICurrentBalance>;
  fetchRefundFee: () => Promise<Record<string, string>>;
  terminalProviders: { [key: string]: any }[];
  fetchTerminalProviders: () => void;
}

const PaymentsDetails = (props: PaymentDetailsProps): JSX.Element => {
  const {
    user,
    org,
    match: { params },
    location,
    showNotification,
    terminalProviders,
    fetchTerminalProviders,
  } = props;
  const { isConfigTagEnabled } = useI18Service();

  const navigate = useNavigate();
  const [isLoading, setIsLoading] = useState(true);
  const [paymentIdDetails, setPaymentIdDetails] = useState<IPaymentDetails | null>(null);
  const [applicationDetails, setApplicationDetails] = useState<ApplicationDetails | null>(null);
  const [paymentIdRefundDetails, setPaymentIdRefundDetails] =
    useState<IPaymentIdRefundDetails | null>(null);
  const [error, setError] = useState(null);
  const [isIssueRefundHidden, setIsIssueRefundHidden] = useState(false);

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
  const splitz = useSplitzService();
  const shouldShowUnregisteredWebsiteError = isUnregisteredWebsiteErrorEnabled({ splitz, user });
  const isPaymentsRoute = location.pathname.includes('/payments');

  const shouldShowOptimizerDetails = user.isOptimizerView();

  const fetchDetails = async () => {
    setError(null);
    setIsLoading(true);
    const { id } = params;
    // slicing the pay_ from the payment id
    const slicedPaymentId = id?.replace('pay_', '') as string;
    try {
      if (shouldShowOptimizerDetails) {
        fetchTerminalProviders();
      }
      if (isPaymentsRoute) {
        const promises = [
          fetchPaymentIdDetails(id, dashboardFlag),
          fetchPaymentIdRefundDetails(id),
        ];

        const isRefundConfigEnabled = isRefundConfigRevampEnabled(splitz);

        // Get refund config for VAS orgs
        if (isRefundConfigEnabled && user?.isVASOrg) {
          promises.push(fetchRefundConfig('org', org?.id.replace('org_', '')));
          promises.push(fetchRefundConfig('merchant', user?.id));
        }

        // payments route - api call flow
        const responses = await Promise.all(promises);

        setPaymentIdDetails(responses[0].data);
        trackDetailsPageLoad({ latestTransactionStatus: responses[0].data.status });
        setPaymentIdRefundDetails(responses[1].data.items);

        if (isRefundConfigEnabled && user?.isVASOrg) {
          const refundConfig = {
            org: responses[2]?.data?.refund_configs || [],
            merchant: responses[3]?.data?.refund_configs || [],
          };
          const isIssueRefundHidden =
            isIssueRefundBtnHidden(responses[0]?.data, refundConfig) || false;

          setIsIssueRefundHidden(isIssueRefundHidden);
        }

        try {
          const appDetailsResponse = await fetchAppDetails(slicedPaymentId);
          let applicationDetails = appDetailsResponse?.data?.application || null;
          if (isEmpty(applicationDetails)) applicationDetails = null;
          setApplicationDetails(applicationDetails);
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

    const isRefundModalRevampEnabled = isRefundRevampEnabled(splitz);
    const modalProps = {
      fetchMerchantBalance: props.fetchCurrentBalance,
      fetchRefundFee: props.fetchRefundFee,
      payment: _payment,
      onRefund: onRefundSuccess,
    };

    const RefundModalComponent = isRefundModalRevampEnabled ? RefundModalRevamp : RefundModal;

    openModal({
      isNew: !!isRefundModalRevampEnabled,
      component: <RefundModalComponent {...modalProps} />,
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

  /**
   * Incase the payment details page is opened in a new tab, navigate(-1) will not work
   * as the history stack is empty. To deal with this case, we explicitly pass navigate('/payments')
   * to the <GoBack /> component
   */
  const goBackHandler =
    location.key === 'default' ? () => navigate('/payments') : (undefined as any);

  return (
    <div className="tabbed-container" data-testid="payments-details">
      <StyledGoBackBtn>
        <GoBack onClickCb={goBackHandler} />
      </StyledGoBackBtn>
      <Box display="flex" gap="spacing.5" flexDirection={flexDirectionSettings}>
        <>
          <Box display="flex" flex="2" gap="spacing.5" flexDirection="column">
            <PaymentDetailsOverview
              paymentDetails={paymentIdDetails}
              paymentIdRefundDetails={paymentIdRefundDetails}
              applicationDetails={applicationDetails}
            />
            {!isDesktop && !isConfigTagEnabled('refunds.refund') && !user.isJnKOmniEnabled ? (
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
                isIssueRefundHidden={isIssueRefundHidden}
              />
            )}
            <PaymentDetailsSection
              paymentDetails={paymentIdDetails}
              applicationDetails={applicationDetails}
              shouldShowOptimizerDetails={shouldShowOptimizerDetails}
              terminalProviders={terminalProviders}
              shouldShowUnregisteredWebsiteError={shouldShowUnregisteredWebsiteError}
            />
            {!isConfigTagEnabled('refunds.refund') && !user.isJnKOmniEnabled && (
              <PaymentRefundDetails
                paymentDetails={paymentIdDetails}
                paymentIdRefundDetails={paymentIdRefundDetails}
                reFetchPageDetails={reFetchPageDetails}
                shouldShowOptimizerDetails={shouldShowOptimizerDetails}
                terminalProviders={terminalProviders}
                isIssueRefundHidden={isIssueRefundHidden}
              />
            )}
          </Box>
          <Box flex="1">
            {isDesktop && (
              <PaymentDetailsTimeline
                paymentIdDetails={paymentIdDetails}
                paymentIdRefundDetails={paymentIdRefundDetails}
                reFetchPageDetails={reFetchPageDetails}
                isIssueRefundHidden={isIssueRefundHidden}
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
  org: state.session.org,
  terminalProviders: state.navigator.terminalProviders,
});

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      ...PaymentActions,
      ...ModalActions,
      showNotification,
      fetchTerminalProviders,
    },
    dispatch,
  );

export default compose<any>(connect(mapStateToProps, mapDispatchToProps))(
  withRouter(PaymentsDetails),
);
