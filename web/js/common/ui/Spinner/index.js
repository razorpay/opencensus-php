// Todo: delete this file, it's available in @dashboard/shared-ui
import React from 'react';

const Spinner = ({ center }) => (
  <div data-testid="spinner" className={`spinner ${center ? 'center' : ''}`}>
    <div className="double-bounce1" />
    <div className="double-bounce2" />
  </div>
);

export default Spinner;
