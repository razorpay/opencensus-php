import { Component } from 'react';
import { connect } from 'react-redux';
import { withRouter } from 'react-router-dom';
import Modal from 'react-modal';
import * as SliderActions from 'merchant_common/reducers/slider';
import { classList } from 'common/utils/rzp-utils';

@withRouter
@connect(
  (state) => ({
    ...state.slider,
    org: state.session.org,
  }),
  SliderActions,
)
export default class ModalSlider extends Component {
  // Closes the slider
  //  1. When slider `Close` button is clicked
  //  2. When clicking on the document except on the Slider view & on any links
  handleDocumentClick = (event) => {
    const target = event.target;

    const powerselectMenu = document.querySelector('body > .tether-element > .PowerSelect__Menu');
    const notification = document.querySelector('body .layout > .Notifications');

    const calendarPicker = document.querySelector('body .rc-calendar-picker');

    const whatsNewTooltip = document.querySelector('.whats-new__tooltip');

    // Fix for power-select dropdown and notification click in slider component
    if (
      (powerselectMenu && powerselectMenu.contains(target)) ||
      (notification && notification.contains(target)) ||
      (calendarPicker && calendarPicker.contains(target)) ||
      (whatsNewTooltip && whatsNewTooltip.contains(target))
    ) {
      return;
    }

    if (!(target.closest('a[href]') || target.closest('.ReactModalPortal'))) {
      this.close();
    }
  };

  componentDidMount() {
    document.addEventListener('click', this.handleDocumentClick, true);
  }

  componentWillUnmount() {
    document.removeEventListener('click', this.handleDocumentClick, true);
    this.props.onClose();
  }

  close = () => {
    this.props.closeSlider();
    if (this.props.closeUrl) {
      this.props.history.push(this.props.closeUrl);
    }
  };

  render() {
    let className = 'ModalSlider__Overlay';
    if (this.props.expanded) {
      className += ' expanded';
    }
    return (
      <Modal
        isOpen={this.props.isOpen}
        closeTimeoutMS={300}
        overlayClassName={className}
        class={classList('ModalSlider__Content', this.props.org.custom_code)}
        contentLabel="SliderModal"
        ariaHideApp={false}
      >
        <button
          type="button"
          className={classList('close close-primary', this.props.closeButtonClass)}
          onClick={this.close}
        >
          <i class="i i-close" />
        </button>

        {this.props.children}
      </Modal>
    );
  }
}

ModalSlider.defaultProps = {
  onClose: () => {},
};
