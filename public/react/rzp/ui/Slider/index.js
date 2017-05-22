import { Component } from 'react';
import { withRouter } from 'react-router-dom';
import Modal from 'react-modal';
import { connect } from 'react-redux';
import * as SliderActions from 'rzp/modules/slider';
import './ModalSlider.styl';

@withRouter
@connect(state => state.slider, SliderActions)
class ModalSlider extends Component {
  // Closes the slider
  //  1. When slider `Close` button is clicked
  //  2. When clicking on the document except on the Slider view, Top Navbar & transaction links

  handleDocumentClick = event => {
    let target = event.target;
    if (
      !(target.closest('.content-wrapper') ||
        target.closest('.ReactModalPortal'))
    ) {
      this.close();
    }
  };

  componentDidMount() {
    document.addEventListener('click', this.handleDocumentClick, true);
  }

  componentWillUnmount() {
    document.addEventListener('click', this.handleDocumentClick);
  }

  componentWillReceiveProps(nextProps) {
    if (nextProps.isOpen && this.props.onOpenURL !== nextProps.onOpenURL) {
      this.props.history.push(nextProps.onOpenURL, {
        notify: false,
      });
    }
  }

  close = () => {
    if (this.props.isOpen) {
      if (this.props.onCloseURL) {
        this.props.history.push(this.props.onCloseURL);
      }
      this.props.closeSlider();
    }
  };

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
          <button type="button" class="close" onClick={this.close}>
            <i class="icon icon-close" />
          </button>

          {component}
        </Modal>
      </div>
    );
  }
}

export default ModalSlider;
