import { Component } from 'react';
import Modal from 'react-modal';
import { connect } from 'react-redux';
import * as SliderActions from 'rzp/modules/slider';
import './ModalSlider.styl';

@connect(state => state.slider, SliderActions)
class ModalSlider extends Component {
  // Closes the slider on document click excluding clicks on top nav bar & transaction links in the table
  handleDocumentClick = event => {
    let target = event.target;
    if (
      !(target.closest('.NavLink__transaction') ||
        target.closest('.navbar-fixed-top') ||
        target.closest('.ReactModalPortal'))
    ) {
      this.props.closeSlider();
    }
  };

  componentDidMount() {
    document.addEventListener('click', this.handleDocumentClick);
  }

  componentWillUnmount() {
    document.addEventListener('click', this.handleDocumentClick);
  }

  componentWillReceiveProps(nextProps) {
    debugger;
  }

  render() {
    let { isOpen, component } = this.props;

    return (
      <div>
        <Modal
          isOpen={isOpen}
          closeTimeoutMS={300}
          overlayClassName="ModalSlider__Overlay"
          class="ModalSlider__Content"
          contentLabel="Modal"
        >
          {component}
        </Modal>
      </div>
    );
  }
}

export default ModalSlider;
