import React, { Component } from 'react';
import Button from 'component/Button';
import ScheduledModal from './ScheduledModal';
import { connect } from 'react-redux';
import * as ModalActions from 'merchant_common/reducers/modals';

@connect(
  state => ({
    user: state.session.user,
  }),
  {
    ...ModalActions,
  }
)
export default class ScheduledBanner extends Component {
  constructor(props) {
    super(props);
  }

  openAutomatic = () => {
    this.props.openModal({
      component: (
        <ScheduledModal
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
    return (
      <div className="pull-right schedule-enable-container">
        <i className="i i-early-settlement scheduled-enable" />
        Get your settlements on the same day, automatically
        <Button.Secondary
          class="scheduled-btn-act"
          onClick={this.openAutomatic}
        >
          Enable Now
        </Button.Secondary>
      </div>
    );
  }
}
