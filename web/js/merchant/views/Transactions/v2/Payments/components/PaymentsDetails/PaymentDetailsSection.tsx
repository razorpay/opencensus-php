import React, { lazy, useEffect, useState } from 'react';
import {
  Box,
  Card,
  CardBody,
  ChevronDownIcon,
  ChevronUpIcon,
  Divider,
  DownloadIcon,
  IconButton,
  Link,
  MailIcon,
  PhoneIcon,
  Text,
} from '@razorpay/blade/components';
import { connect } from 'react-redux';
import { bindActionCreators, compose } from 'redux';
import { withRouter } from 'common/deprecated/withRouter';
import { useMobile } from 'common/hooks/useMobile';
import { useI18Service } from 'common/i18';
import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';
import { withSplitzService } from 'common/splitz';
import { SpiltzContextState } from 'common/splitz/types';
import { User } from 'common/typings';
import copyToClipboard from 'common/utils/copyToClipboard';
import fileDownload from 'common/utils/file-download';
import { getI18FormattedPhoneNumber } from 'merchant/components/Mask/Contact';
import { fetchEncodedPaymentReceipt } from 'merchant/views/Transactions/model';
import { openModal } from 'merchant_common/reducers/modals';
import { showNotification as showNotificationAction } from 'merchant_common/reducers/notifications';
import getNotes from './Notes';
import PaymentMethod from './PaymentMethod';
import PaymentTransfers from './PaymentTransfers';
import Tooltip from './Tooltip';
import {
  CardWrapper,
  CollapsibleContainer,
  CopyWrapper,
  RowsWrapper,
  RowWrapper,
  SectionHeader,
} from './styled';
import { IPaymentDetails, ApplicationDetails } from './types';
import type { RouteComponentProps } from 'common/deprecated/RouteComponentProps';
import { isChargeSlipForPosEnabled, isPosTransaction, onCopy } from './utils';
import { noop } from 'common/utils/rzp-utils';

const PaymentReceipt = lazy(
  () =>
    import(
      /* webpackChunkName: 'PaymentReceipt' */ 'merchant/views/Transactions/v2/Payments/components/PaymentsDetails/PaymentReceipt'
    ),
);

interface IPaymentDetailsSectionProps extends RouteComponentProps<{ id: string }> {
  paymentDetails: IPaymentDetails;
  applicationDetails: ApplicationDetails | null;
  user: User;
  splitz: SpiltzContextState;
  showNotification: (payload: { type: 'error' | 'success'; message: string }) => void;
}

interface DetailRowProps {
  label: string;
  value: React.ReactNode;
  tooltip?: boolean;
  copyable?: boolean;
  onCopyAction?: () => void;
}

const DetailRow: React.FC<DetailRowProps> = ({
  label,
  value,
  tooltip = false,
  copyable = false,
  onCopyAction,
}) => (
  <RowWrapper>
    <Text variant="body" size="medium" weight="regular" color="surface.text.gray.subtle">
      {label} {tooltip && <Tooltip size="small" />}
    </Text>
    {copyable && value !== '--' ? (
      <CopyWrapper onClick={onCopyAction || noop}>
        <Text variant="body" size="medium" weight="semibold" color="surface.text.gray.normal">
          {value}
        </Text>
      </CopyWrapper>
    ) : (
      <Text variant="body" size="medium" weight="regular" color="surface.text.gray.normal">
        {value}
      </Text>
    )}
  </RowWrapper>
);

const PaymentDetailsSection: React.FC<IPaymentDetailsSectionProps> = ({
  paymentDetails,
  applicationDetails,
  history,
  location,
  match: {
    params: { id: transactionIDActual },
  },
  user,
  splitz,
  showNotification,
}) => {
  const [isOpen, setIsOpen] = useState<boolean>(true);
  const isStorefront = location.hash === '#storefront';

  const toggleAccordion = () => setIsOpen((prevState) => !prevState);
  const isMobile = useMobile();

  useEffect(() => {
    // if device type changes to desktop, ensure isOpen is reset to true
    if (!isMobile) {
      setIsOpen(true);
    }
  }, [isMobile]);

  const {
    id,
    acquirer_data = {},
    notes,
    order_id,
    card,
    contact,
    email,
    disputes,
    fee_bearer,
    description,
    method,
    bank,
    vpa,
    wallet,
    invoice_id,
    gateway_terminal_id,
    gateway_merchant_id,
    device_id,
    source_channel,
    payee_vpa,
    device_detail,
  } = paymentDetails;
  const { isConfigTagEnabled } = useI18Service();
  const isChargeSlipExperimentEnabled = isChargeSlipForPosEnabled(splitz);
  const isOmniChannelMerchant =
    isPosTransaction(source_channel) &&
    (user.isOmniEnabledMerchant || (!!user?.pos_activation_status && user?.isOmniChannelMerchant));
  const onDownloadClick = async (e: React.MouseEvent) => {
    e.stopPropagation();
    try {
      const response = await fetchEncodedPaymentReceipt(id);
      fileDownload(response.receipt_encoded_image, `${id}.png`, 'image/png');
    } catch (err) {
      showNotification({ type: 'error', message: 'No Charge Slip found.' });
    }
  };

  const openChargeSlip = () => {
    openModal({
      size: 'medium',
      isNew: true,
      component: (
        <SuspenseWithLoader>
          <PaymentReceipt id={id} />
        </SuspenseWithLoader>
      ),
    });
  };

  const posGatewayId = gateway_terminal_id || payee_vpa;
  const posDeviceSerialNumber = device_id || device_detail;
  return (
    <Box testID="payment-details-section">
      <SectionHeader enableBorderBottomRadius={!isOpen}>
        <Text weight="semibold" size="large" color="surface.text.gray.normal">
          Details
        </Text>
        {isMobile && (
          <CollapsibleContainer onClick={toggleAccordion} data-testid="collapsible-container">
            <Text size="medium" weight="semibold" color="surface.text.gray.subtle">
              {!isOpen ? (
                <ChevronDownIcon
                  size="medium"
                  color="feedback.icon.neutral.intense"
                  data-testid="chevron-down"
                />
              ) : (
                <ChevronUpIcon
                  size="medium"
                  color="feedback.icon.neutral.intense"
                  data-testid="chevron-up"
                />
              )}
            </Text>
          </CollapsibleContainer>
        )}
      </SectionHeader>
      {isOpen && (
        <CardWrapper enableBorderBottomRadius>
          <Card padding="spacing.5" elevation="none">
            <CardBody>
              <RowsWrapper>
                <DetailRow
                  label="Payment ID"
                  value={id}
                  tooltip
                  copyable
                  onCopyAction={onCopy('Payment ID', { transactionIDActual, paymentId: id }).bind(
                    null,
                    id,
                  )}
                />
                <Divider dividerStyle="solid" thickness="thick" variant="muted" />
                <DetailRow label="Bank RRN" value={acquirer_data.rrn || '--'} tooltip />
                <Divider dividerStyle="solid" thickness="thick" variant="muted" />
                <DetailRow
                  label="Order ID"
                  value={order_id || '--'}
                  tooltip
                  copyable
                  onCopyAction={onCopy('Order ID', { transactionIDActual, orderId: order_id }).bind(
                    null,
                    order_id,
                  )}
                />
                <Divider dividerStyle="solid" thickness="thick" variant="muted" />
                <DetailRow
                  label="Invoice ID"
                  value={invoice_id || '--'}
                  copyable
                  onCopyAction={copyToClipboard.bind(null, invoice_id)}
                />
                <Divider dividerStyle="solid" thickness="thick" variant="muted" />
                <DetailRow
                  label="Payment method"
                  value={
                    <PaymentMethod
                      payment={paymentDetails}
                      method={method}
                      card={card}
                      bank={bank}
                      vpa={vpa}
                      wallet={wallet}
                    />
                  }
                />
                <Divider dividerStyle="solid" thickness="thick" variant="muted" />
                <DetailRow
                  label="Customer details"
                  value={
                    <Box display="flex" flexDirection="column" gap="spacing.2">
                      {notes.name ? (
                        <Text
                          variant="body"
                          size="medium"
                          weight="regular"
                          color="surface.text.gray.normal"
                        >
                          {notes.name}
                        </Text>
                      ) : null}
                      {contact ? (
                        <Box display="inline-flex" gap="spacing.3" alignItems="center">
                          <PhoneIcon size="medium" color="interactive.icon.gray.subtle" />
                          <Text
                            variant="body"
                            size="medium"
                            weight="regular"
                            color="surface.text.gray.normal"
                          >
                            {getI18FormattedPhoneNumber(contact)}
                          </Text>
                        </Box>
                      ) : null}
                      {email ? (
                        <Box display="inline-flex" gap="spacing.3" alignItems="center">
                          <MailIcon size="medium" color="interactive.icon.gray.subtle" />
                          <Text
                            variant="body"
                            size="medium"
                            weight="regular"
                            color="surface.text.gray.normal"
                          >
                            {email}
                          </Text>
                        </Box>
                      ) : null}
                      {!notes.name && !contact && !email ? (
                        <Text
                          variant="body"
                          size="medium"
                          weight="regular"
                          color="surface.text.gray.normal"
                        >
                          --
                        </Text>
                      ) : null}
                    </Box>
                  }
                />
                <Divider dividerStyle="solid" thickness="thick" variant="muted" />
                <DetailRow
                  label="Fee bearer"
                  value={
                    fee_bearer === 'platform'
                      ? 'You pay the Razorpay platform fee'
                      : 'The customer has paid the fees for this payment'
                  }
                />
                <Divider dividerStyle="solid" thickness="thick" variant="muted" />
                <DetailRow label="App Name" value={applicationDetails?.name || '--'} />
                <Divider dividerStyle="solid" thickness="thick" variant="muted" />
                <DetailRow label="App ID" value={applicationDetails?.id || '--'} />

                <Divider dividerStyle="solid" thickness="thick" variant="muted" />
                <DetailRow label="Description" value={description || '--'} />
                {isOmniChannelMerchant && (
                  <>
                    {gateway_merchant_id && (
                      <>
                        <Divider dividerStyle="solid" thickness="thick" variant="muted" />
                        <DetailRow label="Payment Gateway ID" value={gateway_merchant_id} />
                      </>
                    )}
                    {posGatewayId ? (
                      <>
                        <Divider dividerStyle="solid" thickness="thick" variant="muted" />
                        <DetailRow
                          label="Device details"
                          value={
                            <Box display="flex" flexDirection="column">
                              <CopyWrapper onClick={copyToClipboard.bind(null, posGatewayId)}>
                                <Text
                                  variant="body"
                                  size="medium"
                                  weight="regular"
                                  color="surface.text.gray.normal"
                                >
                                  {gateway_terminal_id ? 'TID' : 'UPI'}: {posGatewayId}
                                </Text>
                              </CopyWrapper>
                              {posDeviceSerialNumber && (
                                <Text
                                  variant="body"
                                  size="medium"
                                  weight="regular"
                                  color="surface.text.gray.normal"
                                >
                                  DSN: {posDeviceSerialNumber}
                                </Text>
                              )}
                            </Box>
                          }
                        />
                      </>
                    ) : null}
                    {isChargeSlipExperimentEnabled ? (
                      <>
                        <Divider dividerStyle="solid" thickness="thick" variant="muted" />
                        <DetailRow
                          label="Charge Slip"
                          value={
                            <div
                              onClick={openChargeSlip}
                              onKeyDown={openChargeSlip}
                              role="button"
                              tabIndex={0}
                              style={{ cursor: 'pointer' }}
                            >
                              <Box display="flex" flexDirection="row" alignItems="center">
                                <Text
                                  variant="body"
                                  size="medium"
                                  weight="regular"
                                  color="surface.text.primary.normal"
                                >
                                  {id}.png
                                </Text>
                                <IconButton
                                  icon={() => (
                                    <DownloadIcon
                                      marginLeft="spacing.3"
                                      size="medium"
                                      color="interactive.icon.primary.normal"
                                    />
                                  )}
                                  size="medium"
                                  accessibilityLabel="download"
                                  onClick={onDownloadClick}
                                />
                              </Box>
                            </div>
                          }
                        />
                      </>
                    ) : null}
                  </>
                )}
                <Divider dividerStyle="solid" thickness="thick" variant="muted" />
                <DetailRow label="Notes" value={getNotes({ notes, isStorefront })} />
              </RowsWrapper>
              {!isConfigTagEnabled('payment_transfer.transfers') && (
                <PaymentTransfers paymentDetails={paymentDetails} />
              )}
              {disputes.items.length > 0 && (
                <>
                  <Divider dividerStyle="solid" thickness="thick" variant="muted" />
                  <DetailRow
                    label="Dispute ID"
                    value={
                      <Box display="flex" flexDirection="column" gap="spacing.2">
                        {disputes.items.map((item) => (
                          <Link key={item.id} onClick={() => history.push(`/disputes/${item.id}`)}>
                            {item.id}
                          </Link>
                        ))}
                      </Box>
                    }
                  />
                </>
              )}
            </CardBody>
          </Card>
        </CardWrapper>
      )}
    </Box>
  );
};

const mapStateToProps = (state) => ({
  user: state.session.user,
});

function mapDispatchToProps(dispatch) {
  return bindActionCreators(
    {
      showNotification: showNotificationAction,
    },
    dispatch,
  );
}

export default withSplitzService(
  withRouter<any>(compose(connect(mapStateToProps, mapDispatchToProps)(PaymentDetailsSection))),
);
