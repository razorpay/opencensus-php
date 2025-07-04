import React, { Component } from 'react';
import {
  Box,
  Text,
  InfoIcon,
  Amount,
  Tooltip,
  TooltipInteractiveWrapper,
} from '@razorpay/blade/components';
import { connect } from 'react-redux';

import Alert from 'common/ui/Forms/Alert';
import Spinner from 'common/ui/Spinner';
import Time from 'common/ui/Time';
import { isPresent, paiseToRupees, getCountryTaxDefinition } from 'common/utils/rzp-utils';
import { fetchCommission } from 'merchant/reducers/commission';
import { COMMISSION_TYPE } from 'merchant/views/PartnerDashboard/constants';

class CommissionEntityContainer extends Component {
  componentDidMount() {
    this.props.fetchCommission(this.props.id);
  }

  componentDidUpdate(prevProps) {
    if (this.props.id !== prevProps.id) {
      this.props.fetchCommission(this.props.id);
    }
  }

  render() {
    const { loading: isLoading, entity, error, renderDetails, org, user } = this.props;
    const source = entity.source || {};
    const paymentId = source.entity === COMMISSION_TYPE.REFUND ? source.payment_id : source.id;
    const detailsTitle =
      source.entity === COMMISSION_TYPE.REFUND ? 'Refund Details' : 'Payment Details';
    return (
      <div
        className="content-wrapper content-sm txn-details Commission--Detail"
        data-testid="transactional-details-panel"
      >
        {isLoading ? (
          <div className="page-spinner-container">
            <Spinner />
          </div>
        ) : (
          <div className="panel panel-default SliderPanel">
            <div className="panel-heading">Commission Id: {entity.id}</div>
            <Alert type="error" message={error} />
            {isPresent(entity) && (
              <div className="SliderPanel__Body">
                <div className="panel-body">
                  <div className="list-group details-row-container">
                    {/* earnings breakup */}
                    {renderDetails({ ...entity, org, user, sourceType: source.entity })}

                    <Box
                      display="flex"
                      flexDirection="column"
                      gap="spacing.2"
                      marginTop="spacing.9"
                    >
                      <Text weight="semibold">{detailsTitle}</Text>
                      <Box display="flex" flexDirection="column" gap="spacing.0">
                        <Box
                          display="flex"
                          gap="spacing.7"
                          padding={['spacing.4', 'spacing.5']}
                          flex="1"
                          backgroundColor="surface.background.gray.moderate"
                          borderBottomColor="surface.border.gray.subtle"
                          borderTopWidth="none"
                          borderLeftWidth="none"
                          borderRightWidth="none"
                        >
                          <Box flexBasis="30%">
                            <Text>Affiliated Account</Text>
                          </Box>
                          <Box display="flex" flexDirection="column" gap="spacing.0">
                            <Text>{entity.merchant.name}</Text>
                            <Text size="small">{entity.merchant.id}</Text>
                          </Box>
                        </Box>
                        <Box
                          display="flex"
                          gap="spacing.7"
                          alignItems="center"
                          padding={['spacing.4', 'spacing.5']}
                          flex="1"
                          backgroundColor="surface.background.gray.moderate"
                          borderBottomColor="surface.border.gray.subtle"
                          borderTopWidth="none"
                          borderLeftWidth="none"
                          borderRightWidth="none"
                        >
                          <Box flexBasis="30%">
                            <Text>Amount</Text>
                          </Box>
                          <Amount
                            value={paiseToRupees(source.amount)}
                            currency={source.currency}
                            testID="amount-transactional-details"
                          />
                        </Box>
                        {source.entity === COMMISSION_TYPE.REFUND && (
                          <Box
                            display="flex"
                            gap="spacing.7"
                            alignItems="center"
                            padding={['spacing.4', 'spacing.5']}
                            flex="1"
                            backgroundColor="surface.background.gray.moderate"
                            borderBottomColor="surface.border.gray.subtle"
                            borderTopWidth="none"
                            borderLeftWidth="none"
                            borderRightWidth="none"
                          >
                            <Box flexBasis="30%">
                              <Text>Refund ID</Text>
                            </Box>
                            <Text>{source.id}</Text>
                          </Box>
                        )}
                        <Box
                          display="flex"
                          gap="spacing.7"
                          alignItems="center"
                          padding={['spacing.4', 'spacing.5']}
                          flex="1"
                          backgroundColor="surface.background.gray.moderate"
                          borderBottomColor="surface.border.gray.subtle"
                          borderTopWidth="none"
                          borderLeftWidth="none"
                          borderRightWidth="none"
                        >
                          <Box flexBasis="30%">
                            <Text>Payment ID</Text>
                          </Box>
                          <Text>{paymentId}</Text>
                        </Box>
                        <Box
                          display="flex"
                          gap="spacing.7"
                          alignItems="center"
                          padding={['spacing.4', 'spacing.5']}
                          flex="1"
                          backgroundColor="surface.background.gray.moderate"
                        >
                          <Box flexBasis="30%">
                            <Text>Created at</Text>
                          </Box>
                          <Text>
                            <Time value={source.created_at} format="ll" />
                          </Text>
                        </Box>
                      </Box>
                    </Box>
                  </div>
                </div>
              </div>
            )}
          </div>
        )}
      </div>
    );
  }
}

export function CommissionEarningBreakUp(props) {
  const businessName = props.org?.business_name || 'Razorpay';

  // these tokens are currently not supported by blade, hence adding custom tokens
  // based on the figma designs
  const borderColor =
    props.sourceType === COMMISSION_TYPE.PAYMENT ? 'hsla(160,100%,26%,1)' : '#D13821';
  const intent = props.sourceType === COMMISSION_TYPE.PAYMENT ? 'positive' : 'negative';
  const earningsTitle =
    props.sourceType === COMMISSION_TYPE.PAYMENT
      ? `Earnings from ${businessName}`
      : `Earnings reversal due to refund from ${businessName}`;
  return (
    <Box display="flex" flexDirection="column" gap="spacing.3">
      <Box display="flex" gap="spacing.3" alignItems="center">
        <Text weight="semibold">{earningsTitle}</Text>
        <Box display="flex" marginTop="spacing.3">
          {props.sourceType === COMMISSION_TYPE.REFUND ? (
            <Tooltip
              content="These earnings are reversed because of a full or partial refund of the payment from your affiliate account."
              onOpenChange={function noRefCheck() {}}
              placement="bottom"
            >
              <TooltipInteractiveWrapper>
                <InfoIcon size="medium" color="feedback.icon.neutral.intense" />
              </TooltipInteractiveWrapper>
            </Tooltip>
          ) : null}
        </Box>
      </Box>
      <div style={{ borderLeft: `4px solid ${borderColor}` }}>
        <Box
          display="flex"
          gap="140px"
          justifyContent="space-between"
          alignItems="center"
          padding="spacing.5"
          backgroundColor="surface.background.gray.moderate"
        >
          <Box display="flex" flexDirection="column" gap="spacing.2">
            <Text size="large">Total Earnings</Text>
            <Text size="small">
              {`Base + ${getCountryTaxDefinition({
                countryCode: props.user?.merchant?.country_code,
              })}`}
            </Text>
          </Box>
          <Box display="flex" flexDirection="column" gap="spacing.2" alignItems="end">
            <Text color="feedback.text.negative.intense" size="large">
              {intent === 'negative' && '-'}
              <Amount value={props.total} color={intent} currency={props.currency} />
            </Text>
            <Text weight="semibold" size="small">
              <Amount value={props.base} currency={props.currency} />
              +
              <Amount value={props.gst} currency={props.currency} />
            </Text>
          </Box>
        </Box>
      </div>
    </Box>
  );
}

export default connect(
  (state) => ({
    ...state.commission,
    org: state.session.org,
    user: state.session.user,
  }),
  { fetchCommission },
)(CommissionEntityContainer);
