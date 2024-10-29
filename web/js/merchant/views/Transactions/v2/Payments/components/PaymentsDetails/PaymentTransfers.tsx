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

import { getTooltipContent } from './constants';
import { RowWrapper } from './styled';
import { IPaymentTransfers, PaymentStatus } from './types';
import TransferList from './TransferList';
import { fetchTransfersFn } from 'merchant/views/Transactions/model';
import { bindActionCreators } from 'redux';
import ShowWhen from 'merchant/components/ShowWhen';
import { fetchTransfers as fetchTransfersAction } from 'merchant/reducers/payments/details';
import { HIDDEN_INTERNATIONAL_FEATURES_TAGS } from 'merchant/constants/tags';
import { isPlatformTransaction } from 'merchant/views/Transactions/v1/Payments/Utils/platformUtils';
import { useNavigate } from 'react-router-dom';
import { useMobile } from 'common/hooks/useMobile';

const PaymentTransfers = ({
  paymentDetails,
  fetchTransfers,
  transfers,
  orgName,
}: IPaymentTransfers): JSX.Element => {
  const { id, status, amount, amount_transferred } = paymentDetails;
  const navigate = useNavigate();
  const hasPlatformFee = isPlatformTransaction(transfers);
  const isMobile = useMobile();
  const ctaText = isMobile ? 'Create' : 'Create transfer';

  useEffect(() => {
    if (
      [PaymentStatus.CREATED, PaymentStatus.AUTHORIZED, PaymentStatus.FAILED].indexOf(status) < 0
    ) {
      fetchTransfers({ fetchTransfers: fetchTransfersFn(id) });
    }
  }, []);

  return (
    <ShowWhen
      apiFeatureEnabled="Marketplace"
      additionalCondition={(usr) =>
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
            additionalCondition={(user) =>
              status === PaymentStatus.CAPTURED && user.isAllowedEdit('marketplace')
            }
          >
            <Box width="100%" textAlign="right" marginLeft="spacing.5">
              {amount === amount_transferred ? (
                <BladeTooltip content={getTooltipContent(orgName).transfer} placement="top">
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

const mapStateToProps = (state) => {
  return {
    orgName: state.session.org?.business_name,
    transfers: state.payment.transfers,
  };
};

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      fetchTransfers: fetchTransfersAction,
    },
    dispatch,
  );

export default connect(mapStateToProps, mapDispatchToProps)(PaymentTransfers);
