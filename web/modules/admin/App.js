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
        <main />
        <header />
        <aside />
        <ModalContainer />
      </div>
    );
  }
}
