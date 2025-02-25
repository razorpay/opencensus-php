import { getI18FormattedPhoneNumber } from '@dashboards/payments/components/Mask/Contact';
import {
  Box,
  Card,
  CardBody,
  ChevronDownIcon,
  ChevronUpIcon,
  Divider,
  Link,
  MailIcon,
  PhoneIcon,
  Text,
  DownloadIcon,
  IconButton,
} from '@razorpay/blade/components';
import React, { useEffect, useState } from 'react';
import { withRouter } from '@libs/web-nexus/common/deprecated/withRouter';

import { PaymentsDashboardUser } from '@libs/shared-types/payments';
import  SuspenseWithLoader  from '@libs/web-nexus/common/new-ui/SuspenseWithLoader';
import { useStore } from '@federated/apps/shell/commonStore';
import { noop, copyToClipboard, useMobile, lazyImport } from '@libs/shared-utils';
import { fetchEncodedPaymentReceipt } from '../PaymentsList/model';
import getNotes from './Notes';
import PaymentMethod from './PaymentMethod';
import PaymentTransfers from './PaymentTransfers';
import {
  CardWrapper,
  CollapsibleContainer,
  CopyWrapper,
  RowsWrapper,
  RowWrapper,
  SectionHeader,
} from './styled';
import Tooltip from './Tooltip';
import { ApplicationDetails, IPaymentDetails, TooltipKeys } from './types';
import { isPosTransaction, onCopy } from './utils';
import type { RouteComponentProps } from 'apps/self-serve/src/App/Transactions/v2/Payments/types';
import PaymentPagePaymentReceipt from './PaymentPagePaymentReceipt';

const PaymentReceipt = lazyImport(
  () =>
    import(
      /* webpackChunkName: 'PaymentReceipt' */ 'apps/self-serve/src/App/Transactions/v2/Payments/components/PaymentsDetails/PaymentReceipt'
    ),
);

interface IPaymentDetailsSection extends RouteComponentProps<{ id: string }> {
  paymentDetails: IPaymentDetails;
  applicationDetails: ApplicationDetails | null;
  user: PaymentsDashboardUser;
}

interface DetailRowProps {
  label: string;
  value: React.ReactNode;
  tooltipType?: TooltipKeys;
  copyable?: boolean;
  onCopyAction?: () => void;
}

const DetailRow: React.FC<DetailRowProps> = ({
  label,
  value,
  tooltipType = null,
  copyable = false,
  onCopyAction,
}) => (
  <RowWrapper>
    <Text variant="body" size="medium" weight="regular" color="surface.text.gray.subtle">
      {label} {tooltipType ? <Tooltip size="small" type={tooltipType} /> : null}
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

function PaymentDetailsSection({
  paymentDetails,
  applicationDetails,
  history,
  location,
  match: {
    params: { id: transactionIDActual },
  },
  user,
}: IPaymentDetailsSection): React.ReactElement {
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
    upi,
  } = paymentDetails;
  const {showNotification, openModal} = useStore((state) => state);

  const isOmniChannelMerchant =
    isPosTransaction(source_channel) &&
    (user.isOmniEnabledMerchant || (!!user?.pos_activation_status && user?.isOmniChannelMerchant));

  const onDownloadClick = async (e) => {
    e.stopPropagation();
    try {
      const response = await fetchEncodedPaymentReceipt(id);
      const link = document.createElement('a');
      link.href = `data:image/png;base64,${response.receipt_encoded_image}`;
      link.download = `${id}.png`;
      link.click();
    } catch (err) {
      showNotification({
        type: 'error',
        message: 'No Charge Slip found.',
      });
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

  const isPaymentReceiptSectionAllowed = () => {
    if (location.hash) {
      const module = location.hash.substring(1);
      const allowedModules = [
        'paymentpages',
        'paymentbuttons',
        'subscription_buttons',
        'batchpaymentpages',
      ];

      return allowedModules.indexOf(module) > -1;
    }
    return false;
  };

  const posGatewayId = gateway_terminal_id || payee_vpa;
  const posDeviceSerialNumber = device_id || device_detail;
  return (
    <Box testID="payment-details-section">
      <SectionHeader enableBorderBottomRadius={!isOpen}>
        <Text weight="semibold" size="large" color="surface.text.gray.normal">
          Details
        </Text>
        {isMobile ? (
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
        ) : null}
      </SectionHeader>
      {isOpen ? (
        <CardWrapper enableBorderBottomRadius>
          <Card padding="spacing.5" elevation="none">
            <CardBody>
              <RowsWrapper>
                <DetailRow
                  label="Payment ID"
                  value={id}
                  tooltipType="paymentId"
                  copyable
                  onCopyAction={onCopy('Payment ID', { transactionIDActual, paymentId: id }).bind(
                    null,
                    id,
                  )}
                />
                <Divider dividerStyle="solid" thickness="thick" variant="muted" />
                <DetailRow
                  label="Bank RRN"
                  value={acquirer_data.rrn || '--'}
                  tooltipType="bankRRN"
                />
                <Divider dividerStyle="solid" thickness="thick" variant="muted" />
                <DetailRow
                  label="Order ID"
                  value={order_id || '--'}
                  tooltipType="orderId"
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
                {isPaymentReceiptSectionAllowed() ? (
                  <>
                    <Divider dividerStyle="solid" thickness="thick" variant="muted" />
                    <DetailRow
                      label="Payment Receipt"
                      value={<PaymentPagePaymentReceipt paymentId={id} />}
                    />
                  </>
                ) : null}
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
                {user?.isPayerNameEnabled && (
                  <>
                    <Divider dividerStyle="solid" thickness="thick" variant="muted" />
                    <DetailRow label="Payer Name" value={upi?.payer_name || '--'} />
                  </>
                )}
                <Divider dividerStyle="solid" thickness="thick" variant="muted" />
                <DetailRow
                  label="Fee bearer"
                  value={
                    fee_bearer === 'platform'
                      ? `You pay the ${window.rzp_org?.business_name} platform fee`
                      : 'The customer has paid the fees for this payment'
                  }
                />
                <Divider dividerStyle="solid" thickness="thick" variant="muted" />
                <DetailRow label="App Name" value={applicationDetails?.name || '--'} />
                <Divider dividerStyle="solid" thickness="thick" variant="muted" />
                <DetailRow label="App ID" value={applicationDetails?.id || '--'} />

                <Divider dividerStyle="solid" thickness="thick" variant="muted" />
                <DetailRow label="Description" value={description || '--'} />
                {isOmniChannelMerchant ? (
                  <>
                    {gateway_merchant_id ? (
                      <>
                        <Divider dividerStyle="solid" thickness="thick" variant="muted" />
                        <DetailRow label="Payment Gateway ID" value={gateway_merchant_id} />
                      </>
                    ) : null}
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
                                  {gateway_terminal_id ? 'TID' : 'VPA'}: {posGatewayId}
                                </Text>
                              </CopyWrapper>
                              {posDeviceSerialNumber ? (
                                <Text
                                  variant="body"
                                  size="medium"
                                  weight="regular"
                                  color="surface.text.gray.normal"
                                >
                                  DSN: {posDeviceSerialNumber}
                                </Text>
                              ) : null}
                            </Box>
                          }
                        />
                      </>
                    ) : null}
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
                <Divider dividerStyle="solid" thickness="thick" variant="muted" />
                <DetailRow label="Notes" value={getNotes({ notes, isStorefront })} />
              </RowsWrapper>
              <PaymentTransfers paymentDetails={paymentDetails} />
              {disputes.items.length > 0 ? (
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
                    tooltipType="disputeId"
                  />
                </>
              ) : null}
            </CardBody>
          </Card>
        </CardWrapper>
      ) : null}
    </Box>
  );
}

export default withRouter(PaymentDetailsSection);
