import { Component } from 'react';
import { connect } from 'react-redux';
import { withRouter } from 'react-router';
import ModalDialog from 'rzp/ui/ModalDialog';
import Notifications from 'rzp/ui/Notifications';
import ReactIdle from 'rzp/ui/ReactIdle';
import LocalStorageService from 'rzp/utils/localStorage';
import Sidebar from 'merchant/components/Sidebar';
import HeaderNav from 'merchant/components/HeaderNav';
import Content from 'merchant/components/Content';
import Footer from 'merchant/components/Footer';
import MerchantTour from 'merchant/containers/MerchantTour';
import ActivationRequired from 'merchant/components/ActivationRequired';
import IdleWarningDialog from 'merchant/components/IdleWarningDialog';
import * as ModalActions from 'rzp/modules/modals';
import * as NotificationActions from 'rzp/modules/notifications';
import * as SessionActions from 'merchant/modules/session';
import * as ConfigActions from 'merchant/modules/config';
import { applyTheme } from 'rzp/themes';
import User, { setFeatures } from 'merchant/models/User';
import { fetchFeaturesAjax } from 'merchant/modules/config';
import AddGST from 'merchant/containers/Profile/AddGST';
import { fetchGST } from 'merchant/modules/profile';

@withRouter
@connect(state => state.session, {
  ...ModalActions,
  ...SessionActions,
  ...ConfigActions,
  ...NotificationActions,
  fetchGST,
})
export default class App extends Component {
  state = {
    isLoading: true,
    showMobileNav: false,
  };

  componentWillMount() {
    let currentMode = LocalStorageService.getItem('rzp_mode');

    this.props.fetchGST();
    Promise.all([
      this.fetchUser().then(({ data }) => {
        let user = data;
        let role = user.userRole;

        if (!currentMode) {
          currentMode = user.isActivated ? 'live' : 'test';
        } else if (!user.isActivated) {
          currentMode = 'test';
        }

        this.props.updateSession({ mode: currentMode });
        this.redirectToRoute(role);

        return data;
      }),
      this.fetchOrg().then(({ data }) => {
        let orgCode = (this.orgCode = data.custom_code);
        if (orgCode && orgCode !== 'rzp') {
          applyTheme(orgCode);
        }
      }),
    ]).then(response => {
      // Fetch features before displaying other views
      fetchFeaturesAjax(response[0].current).then(data => {
        let user = new User(response[0]);
        user.features = setFeatures(data.data.features);

        this.props.updateSession({ user, mode: currentMode });

        let $splash = document.getElementById('splash');
        $splash.parentElement.removeChild($splash);

        this.setState({ isLoading: false });
      });
    });
  }

  componentWillReceiveProps({ user, history }) {
    if (user.isAuthenticated) {
      let role = user.userRole;
      this.redirectToRoute(role);
    }
  }

  fetchUser() {
    let user = new User(window.rzp_user);
    if (user) {
      delete window.rzp_user;
      this.props.updateSession({ user });
      return Promise.resolve({ data: user });
    } else {
      return this.props.fetchUser();
    }
  }

  fetchOrg() {
    let org = window.rzp_org;
    if (org) {
      delete window.rzp_org;
      this.props.updateSession({ org });
      return Promise.resolve({ data: org });
    } else {
      return this.props.fetchOrg();
    }
  }

  redirectToRoute(role) {
    let pathname = this.props.history.location.pathname;
    let isOldUIEnabled = this.props.user.isOldUIEnabled;

    if (pathname === '/' || pathname === '/dashboard') {
      switch (role) {
        case 'sellerapp':
          let url = isOldUIEnabled ? '/invoices' : '/paymentlinks';
          return this.props.history.replace(url);
        case 'support':
          return this.props.history.replace('/payments');

        case null:
          return this.props.history.replace('/profile');
      }
    }
  }

  switchMode = mode => {
    let user = this.props.user;
    if (mode === 'live' && !user.isActivated) {
      this.props.openModal({
        size: 'small',
        component: <ActivationRequired onCloseClick={this.props.closeModal} />,
      });
    } else {
      LocalStorageService.setItem('rzp_mode', mode);
      location.reload();
    }
  };

  switchMerchant = merchant => {
    this.props
      .switchMerchant(merchant.id)
      .then(() => {
        location.reload();
      })
      .catch(({ errors }) => {
        this.props.showNotification({
          type: 'error',
          message: errors,
        });
      });
  };

  lock = () => {
    let email = this.props.user.user.email;
    return this.props.logout().then(() => {
      location.hash = `/access/lockme/${email}`;
      location.reload();
    });
  };

  showIdleWarning = () => {
    this.props.closeModal();
    this.props.openModal({
      component: <IdleWarningDialog countdown={15} />,
    });
  };

  toggleMobileNav = () => {
    this.setState({
      showMobileNav: !this.state.showMobileNav,
    });
  };

  showGSTModal = () => {
    this.props.openModal({
      size: 'small',
      component: <AddGST openedFromTopbar={true} />,
    });
  };

  render() {
    let { user, org, mode, modeFormatted } = this.props;

    if (this.state.isLoading || !user.isAuthenticated) {
      return null;
    }

    return (
      <div class={`layout ${this.orgCode}`}>
        <HeaderNav
          user={user}
          mode={mode}
          modeFormatted={modeFormatted}
          showGSTModal={this.showGSTModal}
          onSwitchMode={this.switchMode}
          onSwitchMerchant={this.switchMerchant}
          toggleMobileNav={this.toggleMobileNav}
          showMobileNav={this.state.showMobileNav}
        />
        <Sidebar user={user} logoURL={org.main_logo_url} />
        <Content user={user} modeFormatted={modeFormatted} />
        <Footer />

        {/* Creates Portal for the comp */}
        <ModalDialog />
        <Notifications />
        <ReactIdle
          idleDuration={15 * 60}
          warningDuration={15}
          onIdleStart={this.showIdleWarning}
          onIdleEnd={this.props.closeModal}
          onIdleTimeout={this.lock}
        />

        <MerchantTour user={user} />
      </div>
    );
  }
}
