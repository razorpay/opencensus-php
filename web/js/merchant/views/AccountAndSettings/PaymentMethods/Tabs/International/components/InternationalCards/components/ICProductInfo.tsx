import React from 'react';
import {
  StyledProductInfo,
  StyledProductInfoHeader,
  StyledProductInfoContent,
  StyledProductInfoHeading,
  StyledEditTransactionLimitLink,
  StyledRequestToActivateButtonWrapper,
} from 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/components/InternationalCards/Styled';
import {
  ICProductStates,
  ProductTypeForAnalytics,
} from 'merchant/views/AccountAndSettings/PaymentMethods/typings';
import Amount from 'common/ui/Amount';
import { Badge, BadgeProps, Button, EditIcon, InfoIcon } from '@razorpay/blade/components';
import { trackIEEvent } from 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/components/InternationalCards/utils/track';
import { connect } from 'react-redux';
import { Store } from 'common/typings';
import Popover, { PopoverBody } from 'common/ui/Popover';

type Props = {
  title: string;
  description: string;
  settlementCycle?: number;
  transactionSize: number | undefined;
  onRequestAccessClick: (arg0: ProductTypeForAnalytics) => void;
  status: ICProductStates | null;
  product: ProductTypeForAnalytics;
  isMobileDevice: boolean;
};

export const badgeMapping: Partial<
  Record<ICProductStates, { color: NonNullable<BadgeProps['color']>; tooltip: string }>
> = {
  [ICProductStates.ACTIVE]: {
    color: 'positive',
    tooltip: 'International card payments are active on this product',
  },
  [ICProductStates.ACTION_REQUIRED]: {
    color: 'notice',
    tooltip:
      'You need to submit additional details to continue the request for international cards activation',
  },
  [ICProductStates.REJECTED]: {
    color: 'negative',
    tooltip:
      'Your request to activate international card payments on this product is rejected. You can try to request for it again in 90 days',
  },
  [ICProductStates.UNDER_REVIEW]: {
    color: 'information',
    tooltip: 'Your request for international card payment on this product is being verified',
  },
};

export const editTransactionLimitLink =
  '/payments-and-refunds-settings/transaction-limits?action=change-domestic-transaction-limit';

const ProductInfo = ({
  title,
  description,
  settlementCycle,
  transactionSize,
  status,
  product,
  onRequestAccessClick,
  isMobileDevice,
}: Props) => {
  const onEditTransactionLimitClick = () => {
    trackIEEvent({
      objectName: 'Transaction Size Change',
      actionName: 'Clicked',
      properties: {
        product_limit: product,
      },
    });
  };

  const handleRequestActivateClick = () => {
    onRequestAccessClick(product);
    trackIEEvent({
      objectName: `Request To Activate ${product}`,
      actionName: 'Clicked',
    });
  };

  const badgeInfo = status ? badgeMapping[status] : null;

  return (
    <StyledProductInfo>
      <StyledProductInfoHeader>
        <StyledProductInfoHeading>{title}</StyledProductInfoHeading>
        {status === ICProductStates.NOT_ACTIVATED
          ? !isMobileDevice && (
              <Button variant="secondary" size="small" onClick={handleRequestActivateClick}>
                Request to activate
              </Button>
            )
          : !!status && (
              <span>
                <Badge emphasis="intense" size="large" color={badgeInfo?.color} icon={InfoIcon}>
                  {status}
                </Badge>
                <Popover align="top" theme="dark">
                  <PopoverBody>{badgeInfo?.tooltip}</PopoverBody>
                </Popover>
              </span>
            )}
      </StyledProductInfoHeader>
      {status === ICProductStates.ACTIVE ? (
        <StyledProductInfoContent>
          <p>
            Transaction Size Enabled :&nbsp;
            <span>
              <Amount value={transactionSize} currency="INR" />
              <StyledEditTransactionLimitLink
                to={editTransactionLimitLink}
                type="button"
                onClick={onEditTransactionLimitClick}
                data-testid="edit-transaction-link"
              >
                <EditIcon color="interactive.icon.primary.normal" size="medium" />
              </StyledEditTransactionLimitLink>
            </span>
          </p>
          <p>
            Settlement Cycle :&nbsp;
            {settlementCycle !== undefined && (
              <strong>
                T+{settlementCycle} day{settlementCycle > 1 ? 's' : ''} (T is the date of payment
                capture)
              </strong>
            )}
          </p>
        </StyledProductInfoContent>
      ) : (
        <p>{description}</p>
      )}
      {status === ICProductStates.NOT_ACTIVATED && isMobileDevice ? (
        <StyledRequestToActivateButtonWrapper>
          <Button
            variant="secondary"
            size="medium"
            onClick={handleRequestActivateClick}
            isFullWidth
          >
            Request to activate
          </Button>
        </StyledRequestToActivateButtonWrapper>
      ) : null}
    </StyledProductInfo>
  );
};

const mapStateToProps = (state: Store) => ({
  isMobileDevice: state.app.isMobileResolution,
});

export default connect(mapStateToProps, null)(ProductInfo);
