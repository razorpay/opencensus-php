import React from 'react';
import {
  PaymentMethodsStyledTabContentContainer,
  SectionContent,
  SectionHeader,
} from 'merchant/views/AccountAndSettings/PaymentMethods/components/Section/Styled';
import { analyticsTrackWithUserInfo } from 'common/utils/analytics';
import LeafList from 'merchant/views/Settings/PaymentMethods/components/LeafList';
import IntermediateList from 'merchant/views/Settings/PaymentMethods/components/IntermediateList';
import { connect } from 'react-redux';
import { InstrumentListItem, InstrumentsList } from 'common/typings';
import {
  PaymentMethodsFields,
  PaymentMethodsTitles,
} from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/typings/section';
import { User } from 'merchant/views/AccountAndSettings/BusinessSettings/typings';
import { trackLinkClick } from 'merchantLA/containers/TestModeBanner/ga';
import Banner from 'common/ui/Banner';
import { Link } from 'react-router-dom';
import SectionShimmer from 'merchant/views/AccountAndSettings/PaymentMethods/components/Shimmer';

type Props = {
  type: PaymentMethodsFields;
  intermediateInstrument: InstrumentListItem;
  instruments: InstrumentsList;
  user: User;
  loading: boolean;
  children: JSX.Element;
  showLoader?: boolean;
};

const PaymentMethodsSection = ({
  type,
  instruments,
  intermediateInstrument,
  user,
  loading,
  children,
  showLoader = false,
}: Props): JSX.Element => {
  const currentPaymentMethodInfo = React.useMemo(() => {
    return instruments.find((instrument) => instrument.slug === type);
  }, [type, instruments]);

  const onClickKnowMore = () => {
    analyticsTrackWithUserInfo({
      objectName: 'know more',
      actionName: 'clicked',
      screen: 'Payment Methods',
      properties: {
        location: PaymentMethodsTitles[type],
      },
    });
  };

  const isActivatedUser = user.activation_status === 'activated';

  return loading || showLoader ? (
    <SectionShimmer />
  ) : (
    <>
      {!isActivatedUser && (
        <Banner className="no-margin">
          <span>
            <i className="i i-info-outline" /> KYC verification is mandatory to request for new
            payment methods. Please complete your
            <Link to="/activation" onClick={() => trackLinkClick('Go To - Activation Form')}>
              &nbsp; activation form
            </Link>
            , if not done already.
          </span>
        </Banner>
      )}

      <PaymentMethodsStyledTabContentContainer className="content" id="settings-payment-methods">
        <SectionHeader>
          <div>
            <h3>{currentPaymentMethodInfo?.name}</h3>
            <p>{currentPaymentMethodInfo?.description}</p>
          </div>
          <a
            href="https://razorpay.com/docs/payment-gateway/dashboard-guide/settings/payment-methods/"
            target="_blank"
            rel="noopener noreferrer"
            onClick={onClickKnowMore}
          >
            Know More about payment methods{' '}
            <i className="i i-external-link" style={{ marginLeft: '5px' }} />
          </a>
        </SectionHeader>
        <>
          {type !== PaymentMethodsFields.INTERNATIONAL ? (
            <SectionContent className="methods-view">
              {intermediateInstrument &&
                Array.isArray(intermediateInstrument?.intermediateList) && (
                  <IntermediateList instrument={intermediateInstrument} />
                )}
              <LeafList />
            </SectionContent>
          ) : null}
          {children}
        </>
      </PaymentMethodsStyledTabContentContainer>
    </>
  );
};

const mapStateToProps = (state) => {
  return {
    instruments: state.instrumentRequests.pg,
    intermediateInstrument: state.instrumentRequests.intermediateInstrument,
    user: state.session.user,
    loading: state.instrumentRequests.loading,
  };
};

export default connect(mapStateToProps)(PaymentMethodsSection);
