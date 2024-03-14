import React, { useEffect } from 'react';
import { Link } from 'react-router-dom';
import { Amount } from '@razorpay/blade/components';
import styled from 'styled-components';
import { connect } from 'react-redux';
import { compose } from 'redux';
import { useQuery } from '@tanstack/react-query';
import AmountOld from 'common/ui/Amount';
import { paiseToRupees } from 'common/utils/rzp-utils';
import ContentToggler from 'common/ui/Toggler/ContentToggler';
import Definition from 'common/ui/Definition';
import LoaderDots from 'common/ui/LoaderDots';
import { User } from 'common/typings';
import { paymentDetailsOpenedAnalytics } from 'merchant/views/Marketplace/MarketplaceAnalytics';
import { platformFeeCalculator } from 'merchant/views/Transactions/v1/Payments/Utils/platformUtils';
import { fetchPartnerFeeFeature } from 'merchant/views/Marketplace/api';

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
  org: {
    business_name: string;
  };
}

const PlatformFeeDetails = ({ payment, transfers, user, org }: PlatformFeeProps): JSX.Element => {
  const { fee, tax, amount_transferred } = payment;
  // eslint-disable-next-line @typescript-eslint/naming-convention
  const { loading, items } = transfers;

  const { data, isLoading, isError } = useQuery({
    queryKey: ['partner-feature-check'],
    queryFn: fetchPartnerFeeFeature,
    refetchOnWindowFocus: false,
  });
  const isPartnerPlatformFeeEnabled =
    (!isLoading && !isError && data?.data?.feature_enabled) || false;
  const orgName = org.business_name || 'Razorpay';
  const { totalFeeAmount, totalFee, totalPaymentFee, totalTax, partnerFee } = platformFeeCalculator(
    {
      fee,
      tax,
      amount_transferred,
      loading,
      items,
      isPartnerPlatformFeeEnabled,
    },
  );
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
              {isPartnerPlatformFeeEnabled ? orgName : 'Payments'} Fee & Taxes ={' '}
              <Amount value={paiseToRupees(totalPaymentFee)} />
            </AmountContainer>
            <div>
              <SubTextContainer>
                {isPartnerPlatformFeeEnabled ? orgName : 'Payments'} Fee ={' '}
                <Amount value={paiseToRupees(totalFee)} />
              </SubTextContainer>
              <SubTextContainer>
                GST = <Amount value={paiseToRupees(totalTax)} />
              </SubTextContainer>
            </div>
          </ContentToggler>
        </StyledContainer>
        {loading ? (
          <LoaderDots customClass="" />
        ) : (
          <StyledContainer>
            <ContentToggler>
              <AmountContainer>
                {isPartnerPlatformFeeEnabled ? 'Platform Fee' : 'Partner Fee'} ={' '}
                <Amount value={paiseToRupees(partnerFee)} />
              </AmountContainer>
              <div>
                {items.length > 0 ? (
                  items.map((item, index) => {
                    const recipientTransferAmount = item.amount - item.amount_reversed;
                    return (
                      <LinkContainer to={`/route/transfers/${item.id}`} key={`transfers-${index}`}>
                        {`Payment to ${item.recipient_details?.name} = `}
                        <AmountOld value={recipientTransferAmount} />
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

export default compose<any>(
  connect((state) => ({ user: state.session.user, org: state.session.org }), null),
)(PlatformFeeDetails);
