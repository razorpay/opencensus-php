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

interface TncPagePropsT {
  data?: any;
  unAuthorizePage?: boolean;
}

const TnCPage: React.FC<TncPagePropsT> = ({ data, unAuthorizePage }) => {
  const productType = location.pathname.split('/app').join('').split('/tnc/')[1];

  return (
    <View>
      <HeaderWrapper unAuthorizePage={unAuthorizePage}>
        <Header color="white.800" align="center" weight="bold">
          {data ? data?.business_name : 'ABC Corp L.T.D'}
          <HeaderSeprator />
        </Header>
      </HeaderWrapper>
      <Wrapper>
        <Title weight="bold" align="center" size="xxxxlarge">
          Terms and Conditions
        </Title>
        <SubText color="shade.970" size="xxlarge">
          Last updated on{' '}
          {data
            ? convertUnixToDate({ unixTimeStamp: data?.updated_at })
            : convertUnixToDate({ unixTimeStamp: 1620388270 })}
        </SubText>
        {['000000000goods', 'goods'].includes(productType) || data?.deliverable_type === 'goods' ? (
          <GoodsType context={data} />
        ) : (
          <ServicesType context={data} />
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
