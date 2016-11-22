import Modal from 'react-modal'

// Override Modal's default styles
Object.assign(Modal.defaultStyles.overlay, {
  backgroundColor: 'rgba(58, 63, 81, 0.8)',
  zIndex: 9999,
  overflowY: 'auto'
})

Modal.defaultStyles.content = {
  width: '625px',
  margin: '65px auto',
  padding: 0,
  border: 0,
  boxShadow: '0 5px 15px rgba(0,0,0,0.5)',
  backgroundColor: 'rgb(255, 255, 255)',
  borderRadius: '5px'
}

export default Modal
