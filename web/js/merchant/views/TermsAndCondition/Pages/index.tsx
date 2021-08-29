import React from 'react';
import Size from '@razorpay/blade-old/src/atoms/Size';
import View from '@razorpay/blade-old/src/atoms/View';
import GoodsType from './Goods';
import ServicesType from './Services';
import {
  Header,
  HeaderWrapper,
  Footer,
  FooterSeprator,
  FooterText,
  UnderLine,
  HeaderSeprator,
  Wrapper,
  Title,
  SubText,
  SubTitle,
} from './Styled';
import { convertUnixToDate } from 'merchant/views/onboarding/mobile/services/utils';

interface SubHeaderProps {
  children: string;
}

export const SubHeader: React.FC<SubHeaderProps> = ({ children }) => {
  return (
    <View>
      <SubTitle weight="bold">{children}</SubTitle>
      <Size width={4}>
        <UnderLine />
      </Size>
    </View>
  );
};

export interface TncPagePropsT {
  unAuthorizePage?: boolean;
  isOrgAxis: boolean;
  businessName: string;
  updatedAt?: number;
  deliverableType?: string;
  state: string;
  address: string;
  tncLink: string;
  businessModel: string;
  subcategory: string;
  category: string;
  warrantyPeriod?: string;
  email: string;
  refundRequestPeriod: string;
  refundProcessPeriod: string;
}

const TnCPage: React.FC<TncPagePropsT> = ({
  businessName,
  businessModel,
  category,
  subcategory,
  state,
  address,
  tncLink,
  warrantyPeriod,
  refundProcessPeriod,
  refundRequestPeriod,
  updatedAt,
  deliverableType,
  email,
  unAuthorizePage,
  isOrgAxis,
}) => {
  const productType = location.pathname.split('/app').join('').split('/tnc/')[1];

  const commonProps = {
    businessName,
    businessModel,
    category,
    subcategory,
    state,
    address,
    tncLink,
    warrantyPeriod,
    refundProcessPeriod,
    refundRequestPeriod,
    email,
    isOrgAxis,
  };

  return (
    <View>
      <HeaderWrapper unAuthorizePage={unAuthorizePage}>
        <Header color="white.800" align="center" weight="bold">
          {businessName}
          <HeaderSeprator />
        </Header>
      </HeaderWrapper>
      <Wrapper>
        <Title weight="bold" align="center" size="xxxxlarge">
          Terms and Conditions
        </Title>
        <SubText color="shade.970" size="xxlarge">
          Last updated on {convertUnixToDate({ unixTimeStamp: updatedAt })}
        </SubText>
        {productType === '000000000goods' || deliverableType === 'goods' ? (
          <GoodsType {...commonProps} />
        ) : (
          <ServicesType {...commonProps} />
        )}
      </Wrapper>
      <Footer>
        <FooterSeprator />
        <FooterText color="white.800" align="center" _lineHeight="xxlarge">
          By using the website or ordering goods from the website, the buyer agrees to be bound by
          all the terms and conditions
        </FooterText>
      </Footer>
    </View>
  );
};

export default TnCPage;
