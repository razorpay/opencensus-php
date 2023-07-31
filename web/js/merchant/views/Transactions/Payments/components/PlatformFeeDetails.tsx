import React, { useEffect } from 'react';
import { Link } from 'react-router-dom';
import { Amount } from '@razorpay/blade/components';
import styled from 'styled-components';
import { connect } from 'react-redux';
import { compose } from 'redux';
import AmountOld from 'common/ui/Amount';
import { paiseToRupees } from 'common/utils/rzp-utils';
import ContentToggler from 'common/ui/Toggler/ContentToggler';
import Definition from 'common/ui/Definition';
import LoaderDots from 'common/ui/LoaderDots';
import { User } from 'common/typings';
import { paymentDetailsOpenedAnalytics } from 'merchant/views/Marketplace/MarketplaceAnalytics';
import { platformFeeCalculator } from 'merchant/views/Transactions/Payments/Utils/platformUtils';

const AmountContainer = styled.span(({ theme }) => ({
  fontSize: theme.typography.fonts.size[100],
  color: '#58666E',
  display: 'flex',
  alignItems: 'center',
  flexWrap: 'wrap',
}));

const SubTextContainer = styled(AmountContainer)(() => ({
  color: '#7B8199',
}));

const LinkContainer = styled(Link)(({ theme }) => ({
  fontSize: theme.typography.fonts.size[100],
  display: 'flex',
  alignItems: 'center',
  flexWrap: 'wrap',
}));

const StyledContainer = styled.div(({ theme }) => ({
  marginBottom: theme.spacing[3],
}));

interface PlatformFeeProps {
  payment: {
    fee: number;
    tax: number;
    amount_transferred: number;
  };
  transfers: {
    loading: boolean;
    items: {
      tax: number;
      fees: number;
      amount: number;
      recipient_details: { name: string };
      id: string;
      amount_reversed: number;
    }[];
  };
  user: User;
}

const PlatformFeeDetails = ({ payment, transfers, user }: PlatformFeeProps): JSX.Element => {
  const { fee, tax, amount_transferred } = payment;
  // eslint-disable-next-line @typescript-eslint/naming-convention
  const { loading, items } = transfers;
  const { totalFeeAmount, totalFee, totalRazorpayFee, totalTax, platformFee } =
    platformFeeCalculator({ fee, tax, amount_transferred, loading, items });
  useEffect(() => {
    paymentDetailsOpenedAnalytics(user.id);
  }, []);
  return (
    <div>
      <div className="m-b">
        <Definition>
          <Amount value={paiseToRupees(totalFeeAmount)} />
        </Definition>

        <StyledContainer>
          <ContentToggler>
            <AmountContainer>
              Razorpay Fee & Taxes = <Amount value={paiseToRupees(totalRazorpayFee)} />
            </AmountContainer>
            <div>
              <SubTextContainer>
                Razorpay Fee = <Amount value={paiseToRupees(totalFee)} />
              </SubTextContainer>
              <SubTextContainer>
                GST = <Amount value={paiseToRupees(totalTax)} />
              </SubTextContainer>
            </div>
          </ContentToggler>
        </StyledContainer>
        {loading ? (
          <LoaderDots />
        ) : (
          <StyledContainer>
            <ContentToggler>
              <AmountContainer>
                Platform Fee = <Amount value={paiseToRupees(platformFee)} />
              </AmountContainer>
              <div>
                {items.length > 0 ? (
                  items.map((item, index) => {
                    return (
                      <LinkContainer to={`/route/transfers/${item.id}`} key={`transfers-${index}`}>
                        {`Payment to ${item.recipient_details?.name} = `}
                        <AmountOld value={item.amount - item.amount_reversed} />
                      </LinkContainer>
                    );
                  })
                ) : (
                  <span>No Transactions Found</span>
                )}
              </div>
            </ContentToggler>
          </StyledContainer>
        )}
      </div>
    </div>
  );
};

export default compose<any>(connect((state) => ({ user: state.session.user }), null))(
  PlatformFeeDetails,
);
