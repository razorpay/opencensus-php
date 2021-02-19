import React, { Component } from 'react';
import Button from 'common/new-ui/Button';
import ScheduledModal from 'merchant/views/Settlements/Settlements/components/Modals/ScheduledModal';
import { connect } from 'react-redux';
import * as ModalActions from 'merchant_common/reducers/modals';

@connect(
  (state) => ({
    user: state.session.user,
  }),
  {
    ...ModalActions,
  },
)
export default class ScheduledBanner extends Component {
  constructor(props) {
    super(props);
  }

  get settlementRestricted() {
    return this.props.user.isFeatureEnabled('es_on_demand_restricted');
  }

  openAutomatic = () => {
    this.props.openModal({
      component: (
        <ScheduledModal
          eventCategory={this.props.eventCategory}
          fromWhere={this.props.fromWhere}
          onExit={this.props.onExit}
        />
      ),
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
