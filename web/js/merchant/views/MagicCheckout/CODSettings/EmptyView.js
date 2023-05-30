import React from 'react';

function EmptyView({ viewName }) {
  return (
    <div className="empty-container">
      <h1>No data to show</h1>
      <p>Enable COD as a payment option to use {viewName}</p>
    </div>
  );
}

export default EmptyView;
