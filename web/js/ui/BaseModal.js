import React, { Component } from 'react';

/*
  Description: Base modal with header
  Example: Open any pop from side bar in merchant enity details
  Props:
    title: Header of modal. Pass string or custom component or divs
    children: contents of the modal
    banner: pass function (Example: check 'invite a merchant')
    customClass: optional string props
*/
export default ({ header, children, banner, customClass = '' }) => (
  <div class={`base-modal ${customClass}`}>
    <header>{header}</header>
    {banner && banner()}
    <div class="modal-body">{children}</div>
  </div>
);
