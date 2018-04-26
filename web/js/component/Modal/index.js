import PropTypes from 'prop-types';
import { classList } from 'common/util';

/* Dumb Component
* - Modal can be used anywhere and controller by external state whether to show/hide
* - This component only provides UI and control is extended only to parent/redux/etc.
* @props
*   - {Function} onClose,
*   - {Boolean, optional} showCloseBtn, by default close button is shown. Can be hidden if false is passed
*   - {String, optional} id, Helps identify specific Modal to be closed
* */
export default class ModalContainer extends React.PureComponent {
  state = {};

  handleClose = e => {
    const modalContent = document.getElementsByClassName('Modal-content')[0];

    // Don't close modal if clicked inside modal-content (but not on cross btn)
    if (
      modalContent.contains(e.target) &&
      !e.target.classList.contains('Modal-close') > -1
    ) {
      return;
    }

    this.close(e);
  };

  // Fadeout based closing modal
  close = e => {
    this.setState({
      isHidden: true,
    });

    setTimeout(_ => this.props.onClose(e), 400);
  };

  render() {
    const { children, showCloseBtn = true, id } = this.props;
    const modalIdentifier = id || new Date().getTime();

    return (
      <div
        class={classList(
          'Modal-backdrop',
          this.state.isHidden && 'Modal-backdrop--hide'
        )}
        id={modalIdentifier}
        onClick={this.handleClose}
      >
        <div class="Modal-content">
          {showCloseBtn && (
            <span
              class="Modal-close"
              onClick={this.close}
              data-id={modalIdentifier}
            >
              ×
            </span>
          )}
          {children}
        </div>
      </div>
    );
  }
}

ModalContainer.defaultProps = {
  showCloseBtn: true,
};

ModalContainer.propTypes = {
  onClose: PropTypes.func.isRequired,
};
