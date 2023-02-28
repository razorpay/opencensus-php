import React, { useEffect, useMemo } from 'react';
import { bindActionCreators } from 'redux';
import { SettlementReducerState, User } from 'common/typings';
import { connect } from 'react-redux';
import ICProductInfo from './ICProductInfo';
import {
  StyledICProductsSection,
  StyledProductInfo,
} from 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/components/InternationalCards/Styled';
import {
  ICProductStates,
  ProductTypeForAnalytics,
} from 'merchant/views/AccountAndSettings/PaymentMethods/typings';
import { fetchSchedule as fetchScheduleAction } from 'merchant/reducers/settlements/details';
import Non3dsCardsActivation from 'merchant/views/Settings/PaymentMethods/components/Non3dsCardsActivation/Non3dsCardsActivation';

type Props = {
  user: User;
  settlement: SettlementReducerState;
  onRequestAccessClick: (triggerSource: string) => void;
  pgProductState: ICProductStates | null;
  ppliProductState: ICProductStates | null;
  fetchSchedule: () => void;
};

const ICProductsInfo = ({
  user,
  settlement,
  onRequestAccessClick,
  pgProductState,
  ppliProductState,
  fetchSchedule,
}: Props) => {
  const settlementDelay = useMemo(
    () =>
      settlement.schedule.data.find((item) => item.method === null && item.international === 1)
        ?.delay,
    [settlement.schedule.data],
  );

  useEffect(() => {
    fetchSchedule();
  }, []);

  const handleRequestAccessClick = (productType: ProductTypeForAnalytics) => {
    onRequestAccessClick(productType === ProductTypeForAnalytics.PG ? 'pg' : 'others');
  };

  const maxPaymentAmount = user?.merchant?.max_payment_amount;

  return (
    <StyledICProductsSection>
      <StyledProductInfo>
        <Non3dsCardsActivation isIERevamp />
      </StyledProductInfo>

      <ICProductInfo
        title="Payment Gateway"
        description="Activate international card payments on payment gateway after website registration"
        settlementCycle={settlementDelay}
        transactionSize={maxPaymentAmount}
        status={pgProductState}
        onRequestAccessClick={handleRequestAccessClick}
        product={ProductTypeForAnalytics.PG}
      />
      <ICProductInfo
        title="Payment pages, payment links, and invoices"
        description="Activate international card payments on payment pages, payment links, and invoices without website requirement"
        settlementCycle={settlementDelay}
        transactionSize={maxPaymentAmount}
        status={ppliProductState}
        onRequestAccessClick={handleRequestAccessClick}
        product={ProductTypeForAnalytics.PPLI}
      />
    </StyledICProductsSection>
  );
};

const mapStateToProps = (state) => ({
  user: state.session.user,
  settlement: state.settlement,
});

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators({ fetchSchedule: fetchScheduleAction }, dispatch);
};

export default connect(mapStateToProps, mapDispatchToProps)(ICProductsInfo);
