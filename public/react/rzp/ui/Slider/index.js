import { Component } from 'react';
import { connect } from 'react-redux';
import { withRouter } from 'react-router-dom';
import Modal from 'react-modal';
import * as SliderActions from 'rzp/modules/slider';
import { isNone } from 'rzp/utils/rzp-utils';
import './ModalSlider.styl';

@withRouter
@connect(state => state.slider, SliderActions)
export default class ModalSlider extends Component {
  // Closes the slider
  //  1. When slider `Close` button is clicked
  //  2. When clicking on the document except on the Slider view & on any links
  handleDocumentClick = event => {
    let target = event.target;

    // Fix for power-select dropdown in slider component
    if (
      document.querySelector('body > .tether-element') &&
      document.querySelector('body > .tether-element').contains(target)
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
    var className = 'ModalSlider__Overlay';
    if (this.props.expanded) {
      className += ' expanded';
    }
    return (
      <Modal
        isOpen={this.props.isOpen}
        closeTimeoutMS={300}
        overlayClassName={className}
        class="ModalSlider__Content"
        contentLabel="SliderModal"
      >
        <button type="button" class="close close-primary" onClick={this.close}>
          <i class="icon icon-close" />
        </button>

        {this.props.children}
      </Modal>
    );
  }
}

ModalSlider.defaultProps = {
  onClose: () => {},
};
