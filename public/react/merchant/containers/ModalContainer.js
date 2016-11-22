import { Component } from 'react'

export default class ModalContainer extends Component {
  constructor() {
    super(...arguments)
    this.state = {
      isModalOpen: false
    }

    this.openModal = ::this.openModal
    this.closeModal = ::this.closeModal
  }

  openModal() {
    this.setState({
      isModalOpen: true
    })
  }

  closeModal() {
    this.setState({
      isModalOpen: false
    })
  }
}
