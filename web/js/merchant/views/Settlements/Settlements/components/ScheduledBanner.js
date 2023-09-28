import React, { Component } from 'react';
import { withRouter } from 'common/deprecated/withRouter';
import Button from 'common/new-ui/Button';
import ScheduledModal from 'merchant/views/Settlements/Settlements/components/Modals/ScheduledModal';
import { connect } from 'react-redux';
import * as ModalActions from 'merchant_common/reducers/modals';
import { trackEnableNow } from 'merchant/views/Settlements/trackEvents';
import { bindActionCreators } from 'redux';

class ScheduledBanner extends Component {
  get settlementRestricted() {
    return this.props.user.isFeatureEnabled('es_on_demand_restricted');
  }

  openAutomatic = () => {
    trackEnableNow(this.props.location.pathname);
    this.props.openModal({
      component: <ScheduledModal />,
      size: 'small',
      disableClose: true,
    });
  };

  openAutomaticViaProp = () => {
    if (this.props.openAutoModal) {
      this.openAutomatic();
    }
  };

  componentDidMount() {
    this.openAutomaticViaProp();
  }

  componentDidUpdate() {
    this.openAutomaticViaProp();
  }

  render() {
    if (this.props.user.isAutomaticSettlementEnabled) return null;

    return (
      <div>
        <i class="i i-early-settlement scheduled-enable" />
        Get your settlements on the same day, automatically.
        <Button.Transparent
          className="enable-now-btn"
          onClick={this.openAutomatic}
          disabled={this.settlementRestricted}
        >
          Enable Now
        </Button.Transparent>
      </div>
    );
  }
}

const mapStateToProps = (state) => {
  return {
    user: state.session.user,
  };
};

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators({ ...ModalActions }, dispatch);
};

export default withRouter(connect(mapStateToProps, mapDispatchToProps)(ScheduledBanner));
