import { PropTypes } from 'react'
import Modal from 'react-modal'

const ConfirmModal = (props, context) => {
  let confirmModelStyle = {
    overlay: Object.assign({}, Modal.defaultStyles.overlay, {
      zIndex: 10000
    }),
    content: Object.assign({}, Modal.defaultStyles.content, {
      width: '325px'
    })
  }

  return (
    <div>
      <Modal
        isOpen={props.show}
        style={confirmModelStyle}
        shouldCloseOnOverlayClick={false}
        closeTimeoutMS={300} >

        <div class='modal-header'>
          <h3 class='modal-title'>{props.options.header || 'Alert'}</h3>
        </div>

        <div class='modal-body'>
          <h4>{props.options.message}</h4>
        </div>

        <div class='modal-footer'>
          <button type='button' class='btn btn-default' onClick={props.onAbort}>Cancel</button>
          <button type='button' class='btn btn-primary' onClick={props.onAffirm}>Ok</button>
        </div>
      </Modal>
    </div>
  )
}

ConfirmModal.defaultProps = {
  show: false,
  options: {
    message: 'Are you sure to continue ?'
  }
}

ConfirmModal.propTypes = {
  show: PropTypes.bool.isRequired,
  options: PropTypes.object,
  onAbort: PropTypes.func,
  onAffirm: PropTypes.func
}


export default ConfirmModal
