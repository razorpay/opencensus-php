// TODO: Fix the imports, currently out of scope
// @ts-nocheck
import React, { useEffect } from 'react';
import {
  Box,
  Button,
  Divider,
  Text,
  Tooltip as BladeTooltip,
  TooltipInteractiveWrapper,
} from '@razorpay/blade/components';
import { connect } from 'react-redux';

import { fetchTransfersFn } from 'apps/self-serve/src/App/Transactions/model';
import { AnyAction, Dispatch, bindActionCreators } from 'redux';
import ShowWhen from 'shell/components/ShowWhen';
import { fetchTransfers as fetchTransfersAction } from 'merchant/reducers/payments/details';
import { HIDDEN_INTERNATIONAL_FEATURES_TAGS } from 'merchant/constants/tags';
import { isPlatformTransaction } from 'apps/self-serve/src/App/Transactions/v1/Payments/Utils/platformUtils';
import { useNavigate } from 'react-router-dom';
import { useMobile } from '@dashboard/shared-ui/hooks';
import { getTooltipContent } from './constants';
import TransferList from './TransferList';
import { IPaymentTransfers, PaymentStatus } from './types';
import { RowWrapper } from './styled';

const PaymentTransfers = ({
  paymentDetails,
  fetchTransfers,
  transfers,
}: IPaymentTransfers): JSX.Element => {
  const { id, status, amount, amount_transferred } = paymentDetails;
  const navigate = useNavigate();
  const hasPlatformFee = isPlatformTransaction(transfers);
  const isMobile = useMobile();
  const ctaText = isMobile ? 'Create' : 'Create transfer';

  useEffect(() => {
    if (![PaymentStatus.CREATED, PaymentStatus.AUTHORIZED, PaymentStatus.FAILED].includes(status)) {
      fetchTransfers({ fetchTransfers: fetchTransfersFn(id) });
    }
  }, []);

  return (
    <ShowWhen
      apiFeatureEnabled="Marketplace"
      additionalCondition={(usr: any) =>
        usr.isOrgCurlec ||
        (!usr.findTag(HIDDEN_INTERNATIONAL_FEATURES_TAGS.PaymentTransfers) && !hasPlatformFee)
      }
    >
      <Divider dividerStyle="solid" thickness="thick" variant="muted" />

      <RowWrapper>
        <Box display="flex" justifyContent="space-between" width="100%">
          <Box
            display="flex"
            width="100%"
            flexDirection={{
              base: 'column',
              m: 'row',
            }}
          >
            <Box minWidth="200px">
              <Text variant="body" size="medium" weight="regular" color="surface.text.gray.subtle">
                Transfer
              </Text>
            </Box>
            <TransferList transfers={transfers} />
          </Box>
          <ShowWhen
            additionalCondition={(user: any) =>
              status === PaymentStatus.CAPTURED && user.isAllowedEdit('marketplace')
            }
          >
            <Box width="100%" textAlign="right" marginLeft="spacing.5">
              {amount === amount_transferred ? (
                <BladeTooltip
                  content={getTooltipContent(window.rzp_org?.business_name).transfer}
                  placement="top"
                >
                  <TooltipInteractiveWrapper>
                    <Button isDisabled size="small" variant="secondary">
                      {ctaText}
                    </Button>
                  </TooltipInteractiveWrapper>
                </BladeTooltip>
              ) : (
                <Button
                  size="small"
                  variant="secondary"
                  onClick={() => navigate(`/payments/${id}/v2/transfers/new`)}
                >
                  {ctaText}
                </Button>
              )}
            </Box>
          </ShowWhen>
        </Box>
      </RowWrapper>
    </ShowWhen>
  );
};

const mapStateToProps = (state: any) => {
  return {
    transfers: state.payment.transfers,
  };
};

const mapDispatchToProps = (dispatch: Dispatch<AnyAction>) =>
  bindActionCreators(
    {
      fetchTransfers: fetchTransfersAction,
    },
    dispatch,
  );

export default connect(mapStateToProps, mapDispatchToProps)(PaymentTransfers);
