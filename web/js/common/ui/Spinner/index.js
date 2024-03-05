// Todo: delete this file, it's available in @dashboard/shared-ui
import React from 'react';

const Spinner = ({ center }) => (
  <div data-testid="spinner" class={`spinner ${center ? 'center' : ''}`}>
    <div class="double-bounce1" />
    <div class="double-bounce2" />
  </div>
);

export default Spinner;
