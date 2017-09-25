import { Component } from 'react';
import { connect } from 'react-redux';
import { NavLink } from 'react-router-dom';
import HeaderAction from 'rzp/ui/HeaderAction';
import DataTable from 'rzp/ui/Table/DataTable';
import ListContainer from 'merchant/containers/ListContainer';
import VirtualAccountsListFilter from 'merchant/components/VirtualAccounts/ListFilter';
import CreateVirtualAccount from './CreateVirtualAccount';
import { updateFeatures } from 'merchant/modules/config';
import { showNotification } from 'rzp/modules/notifications';
import { fetchConfig } from 'merchant/modules/config';
import { openModal, closeModal } from 'rzp/modules/modals';
import { fetchVirtualAccounts as fetchAll } from 'merchant/modules/virtualaccounts';
import {
  virtualAccountId,
  accountDescription,
  amountPaid,
  status,
  createdAt,
} from 'rzp/ui/item/pair';
import TestModeBanner from 'merchant/containers/TestModeBanner';
import ActivationBanner from 'merchant/components/ActivationBanner';
import FeatureOnboarding from 'merchant/containers/FeatureOnboarding/OnBoarding';
import FeatureOnboardingModal from 'merchant/containers/FeatureOnboarding/OnBoardingModal';

const heading =
  'A powerful system to easily collect payments via direct bank transfers (NEFT/RTGS). Automate the tedious reconciliation process, starting now.';

@connect(
  state => {
    return {
      ...state.virtualaccounts,
      user: state.session.user,
      mode: state.session.mode,
    };
  },
  {
    fetchAll,
    fetchConfig,
    openModal,
    closeModal,
    updateFeatures,
    showNotification,
  }
)
export default class VirtualAccountsListContainer extends ListContainer {
  componentWillMount() {
    // TODO: Don't call below when feature is disbaled
    super.componentWillMount();
    this.props.fetchConfig();
  }

  enableFeature = () => {
    var data = {
      features: {
        virtual_accounts: 1,
      },
    };

    return this.props
      .updateFeatures(data, this.props.user.current)
      .then(res => {
        this.props.showNotification({
          type: 'success',
          message: 'Razorpay Smart Collect has been enabled!',
        });
        setTimeout(() => location.reload());
      })
      .catch(err => {
        this.props.showNotification({
          type: 'error',
          message: err.errors,
        });
      });
  };

  showCreateVAModal = () => {
    this.props.openModal({
      size: 'small',
      component: (
        <CreateVirtualAccount showCreateVAModal={this.showCreateVAModal} />
      ),
    });
  };

  openActivationModal = () => {
    this.props.openModal({
      component: (
        <FeatureOnboardingModal
          onClose={this.props.closeModal}
          heading="Razorpay Smart Collect"
          description={heading}
          formType="virtual_accounts"
          isTestMode={false}
        />
      ),
      size: 'large',
    });
  };

  render() {
    let featureEnabled = this.props.user.isVirtualAccountsEnabled;
    if (!featureEnabled) {
      return (
        <FeatureOnboarding
          heading="Razorpay Smart Collect"
          description={heading}
          formType="virtual_accounts"
          isTestMode={this.props.mode === 'test'}
          enableFeatureInTestMode={this.enableFeature}
        />
      );
    }

    return (
      <div>
        {this.props.mode === 'test' &&
          <ActivationBanner
            productName="Razorpay Smart Collect"
            productDocs="https://razorpay.com/docs/smart-collect"
            feature="virtual_accounts"
            symbol={require('styles/assets/symbols/smartcollect.svg')}
            onActivate={this.openActivationModal}
          />}
        <tabbed-container>
          <header id="#va-header">
            <NavLink to="/virtualaccounts">Virtual Accounts</NavLink>

            <HeaderAction>
              <div class="btn-toolbar">
                <button
                  class="btn btn-primary"
                  onClick={this.showCreateVAModal}
                >
                  <i class="icon icon-plus" />
                  <span>Create Virtual Account</span>
                </button>
              </div>
            </HeaderAction>
          </header>
          <TestModeBanner />

          <content>
            <div class="content-wrapper">
              <VirtualAccountsListFilter
                form="virtualAccountsListFilter"
                count={this.state.count}
                onSubmit={this.search}
              />

              <DataTable
                title="Virtual Accounts"
                columns={[
                  virtualAccountId,
                  accountDescription,
                  amountPaid,
                  status,
                  createdAt,
                ]}
                count={this.state.count}
                skip={this.state.skip}
                paginate={this.paginate}
                {...this.props}
              />
            </div>
          </content>
        </tabbed-container>
      </div>
    );
  }
}
