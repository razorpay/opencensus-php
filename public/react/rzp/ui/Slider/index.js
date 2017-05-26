import { Component } from 'react';
import { withRouter } from 'react-router-dom';
import Modal from 'react-modal';
import './ModalSlider.styl';

@withRouter
export default class ModalSlider extends Component {
  state = {
    isOpen: true,
  };

  close = () => {
    this.setState({ isOpen: false });
    if (this.props.closeUrl) {
      this.props.history.push(this.props.closeUrl);
    }
  };

  render() {
    return (
      <Modal
        isOpen={this.state.isOpen}
        closeTimeoutMS={300}
        overlayClassName="ModalSlider__Overlay"
        class="ModalSlider__Content"
        contentLabel="Modal"
      >
        <button type="button" class="close" onClick={this.close}>
          <i class="icon icon-close" />
        </button>

        {this.props.children}
      </Modal>
    );
  }
}
