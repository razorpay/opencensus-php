import React, { Component } from 'react';
import ModalContainer, { openModal, closeModal } from 'store/modal';

export default class App extends Component {
  state = {
    user: window.rzp_user,
    org: window.rzp_org,
  };

  render() {
    return (
      <div id="app-container">
        <ModalContainer />
        <aside />
        <main onClick={() => openModal({ component: <span>*</span> })}>
          Add
        </main>
        <main onClick={closeModal}>Remove</main>
      </div>
    );
  }
}
