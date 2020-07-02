import { connect } from 'react-redux';
import { withRouter } from 'react-router-dom';

import { RZPFeatures } from 'merchant/helpers/data';
import {
  buttonTitle,
  itemName,
  unitsSold,
  createdAt,
} from 'common/ui/item/pair';

import Amount from 'common/ui/Amount';
import DataTable from 'common/ui/Table/DataTable';
import HeaderAction from 'common/ui/HeaderAction';
import DocsLink from 'merchant/components/DocsLink';
import ListContainer from 'merchant/containers/ListContainer';
import TakeATourButton from 'merchant/components/QuickGuide/TakeATourButton';
import { PaymentPagesStatusLabel } from 'merchant/components/StatusLabel';

import ListFilter from './ListFilter';
import GetCodeModal from '../components/GetCodeModal'; // SuccessModal

import { handleProductQuickGuide } from 'merchant/reducers/onboarding';
import { openModal } from 'merchant_common/reducers/modals';
import { fetchPaymentButtonsList as fetchAll } from 'merchant/reducers/paymentbuttons/list';
import { setIsPaymentButtonCodeUsed } from '../../utils';

const getActions = openGetCodeModal => ({
  title: 'Actions',
  columnClass: 'action-col',
  value: item => (
    <a class="get-code-btn" onClick={() => openGetCodeModal(item)}>
      GET BUTTON CODE
    </a>
  ),
});

const totalSales = {
  title: 'Total Sales',
  value: item => (
    <Amount value={item.total_amount_paid} currency={item.currency} />
  ),
};

export const status = {
  columnClass: 'status-col',
  title: 'Status',
  value: item => <PaymentPagesStatusLabel status={item.status} />,
};

@withRouter
@connect(
  state => ({
    ...state.paymentbuttons,
    ...state.session,
  }),
  { fetchAll, openModal, handleProductQuickGuide }
)
export default class PaymentButtonsList extends ListContainer {
  state = {
    isPaymentButtonOpen: false,
  };

  openGetCodeModal = paymentButtonEntity => {
    this.props.openModal({
      size: 'medium',
      className: 'GetCodeModal',
      component: (
        <GetCodeModal
          title="Copy Button Code"
          paymentButton={paymentButtonEntity}
        />
      ),
    });
  };

  handleProductQuickGuide = () => {
    this.props.handleProductQuickGuide({
      feature: RZPFeatures.PB,
      showOnboarding: true,
      isQuickGuideOpen: true,
      isTour: true,
    });

    this.resetCopyPasteCodeStatus();
  };

  resetCopyPasteCodeStatus = () => {
    setIsPaymentButtonCodeUsed(
      {
        mid: this.props.user.current,
        mode: this.props.mode,
      },
      false
    );
  };

  openPaymentButtonsNewPage = () => {
    this.setState(
      {
        isPaymentButtonOpen: true,
      },
      () => {
        this.props.history.push('/paymentbuttons/new');
      }
    );
  };

  render() {
    const { user } = this.props;
    const isRoleAllowedEdit = user.isAllowedEdit('payment_buttons');

    return (
      <div class="PaymentButtons--ListingPage content-wrapper">
        <HeaderAction>
          <div class="btn-toolbar pull-right">
            <TakeATourButton
              feature={RZPFeatures.PB}
              onClick={this.resetCopyPasteCodeStatus}
            />

            <DocsLink url="https://razorpay.com/docs/payment-button/" />

            {isRoleAllowedEdit &&
              user.isPaymentButtonEnabledByRazorX && (
                <span
                  class="btn btn-primary"
                  onClick={this.openPaymentButtonsNewPage}
                >
                  <i class="i i-plus" />
                  <span>Create Payment Button</span>
                </span>
              )}
          </div>
        </HeaderAction>

        <ListFilter
          form="paymentButtonListFilter"
          count={this.state.count}
          onSubmit={this.search}
        />

        <DataTable
          title="Payment Buttons"
          columns={[
            buttonTitle,
            totalSales,
            itemName,
            unitsSold,
            createdAt,
            status,
            getActions(this.openGetCodeModal),
          ]}
          count={this.state.count}
          skip={this.state.skip}
          paginate={this.paginate}
          {...this.props}
          EmptyComponent={EmptyComponent}
        />
      </div>
    );
  }
}

const EmptyComponent = () => (
  <div class="PaymentButton-empty-list">
    <img src="/dist/css/assets/payment_button/empty-list.svg" />

    <div class="description">
      <h4>It’s Lonely Here!</h4>
      <div>Create a Payment Button to get Started</div>
      <br />
      Not sure where to start? See our getting{' '}
      <a target="_blank" href="https://razorpay.com/docs/payment-button/">
        started guide <i class="i i-external-link" />
      </a>
    </div>
  </div>
);
