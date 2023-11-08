import { Component } from 'react';
import {
  Box,
  Heading,
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
import { isPresent, paiseToRupees } from 'common/utils/rzp-utils';
import { fetchCommission } from 'merchant/reducers/commission';
import { COMMISSION_TYPE } from 'merchant/views/PartnerDashboard/constants';

@connect(
  (state) => ({
    ...state.commission,
    org: state.session.org,
    user: state.session.user,
  }),
  { fetchCommission },
)
export default class CommissionEntityContainer extends Component {
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
        class="content-wrapper content-sm txn-details Commission--Detail"
        data-testid="transactional-details-panel"
      >
        {isLoading ? (
          <div class="page-spinner-container">
            <Spinner />
          </div>
        ) : (
          <div class="panel panel-default SliderPanel">
            <div class="panel-heading">Commission Id: {entity.id}</div>
            <Alert type="error" message={error} />
            {isPresent(entity) && (
              <div class="SliderPanel__Body">
                <div class="panel-body">
                  <div class="list-group details-row-container">
                    {/* earnings breakup */}
                    {renderDetails({ ...entity, org, user, sourceType: source.entity })}

                    <Box
                      display="flex"
                      flexDirection="column"
                      gap="spacing.2"
                      marginTop="spacing.9"
                    >
                      <Text weight="bold">{detailsTitle}</Text>
                      <Box display="flex" flexDirection="column" gap="spacing.0">
                        <Box
                          display="flex"
                          gap="spacing.7"
                          padding={['spacing.4', 'spacing.5']}
                          flex="1"
                          backgroundColor="surface.background.level3.lowContrast"
                          borderBottomColor="surface.border.subtle.lowContrast"
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
                          backgroundColor="surface.background.level3.lowContrast"
                          borderBottomColor="surface.border.subtle.lowContrast"
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
                            backgroundColor="surface.background.level3.lowContrast"
                            borderBottomColor="surface.border.subtle.lowContrast"
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
                          backgroundColor="surface.background.level3.lowContrast"
                          borderBottomColor="surface.border.subtle.lowContrast"
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
                          backgroundColor="surface.background.level3.lowContrast"
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
  const isRzpOrg = props?.user?.isOrgRZP;

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
        <Text weight="bold">{earningsTitle}</Text>
        <Box display="flex" marginTop="spacing.3">
          {props.sourceType === COMMISSION_TYPE.REFUND ? (
            <Tooltip
              content="These earnings are reversed because of a full or partial refund of the payment from your affiliate account."
              onOpenChange={function noRefCheck() {}}
              placement="bottom"
            >
              <TooltipInteractiveWrapper>
                <InfoIcon size="medium" color="feedback.icon.neutral.lowContrast" />
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
          backgroundColor="surface.background.level3.lowContrast"
        >
          <Box display="flex" flexDirection="column" gap="spacing.2">
            <Heading>Total Earnings</Heading>
            <Text size="small">{`Base + ${isRzpOrg ? 'GST' : 'Tax'}`}</Text>
          </Box>
          <Box display="flex" flexDirection="column" gap="spacing.2" alignItems="end">
            <Heading color="feedback.text.negative.lowContrast">
              {intent === 'negative' && '-'}
              <Amount value={props.total} intent={intent} currency={props.currency} />
            </Heading>
            <Text weight="bold" size="small">
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
