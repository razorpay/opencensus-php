import EasterEgg from 'merchant/components/EasterEgg';
import Pager from 'common/ui/Pager';
import List from 'merchant/views/PaymentLinks/PaymentLinks/List';
import { EmptyListWithTableRow } from 'merchant/components/EmptyList';

jest.mock('common/utils/analytics', () => ({
  ...jest.requireActual('common/utils/analytics'),
  analyticsTrack: jest.fn(),
}));

jest.mock('common/ui/HeaderAction', () => ({ children }) => <div>{children}</div>);
jest.mock('merchant/components/ShowWhen', () => ({ children }) => <div>{children}</div>);

export const onSubmitMock = jest.fn();
export const onSearchAnalyticsMock = jest.fn();
export const onClearAnalyticsMock = jest.fn();
const paginationOnClick = jest.fn();

const initProps = {
  onSubmit: onSubmitMock,
  onSearchAnalytics: onSearchAnalyticsMock,
  onClearAnalytics: onClearAnalyticsMock,
};

export const FallbackComponent = () => {
  return (
    <div className="inline-fallback">
      <div>There was an issue, please try later!</div>
      <div>Error code</div>
    </div>
  );
};

export const user = {
  isPaymentlinksV2Enabled: true,
  isInttCurrenciesEnabled: false,
  isPaymentlinksV2CompatEnabled: true,
  isAllowedView: () => true,
};

export const org = {
  custom_code: 'rzp',
};

export const EmptyComponentApp = () => (
  <EmptyListWithTableRow
    colSpan={8}
    description={
      <>
        <div>There are no payment links yet!!</div>
        <div>Start creating new links now.</div>
      </>
    }
  />
);

export const EasterEggApp = (props) => {
  return <EasterEgg extraClass="ftx-payment-links" page="Payment Links" {...props} />;
};

export const App = (props) => {
  return <List {...initProps} {...props} />;
};

export const renderPager = () => {
  return <Pager onClick={paginationOnClick} count={20} skip={5} length={10} />;
};
