import React from 'react';

// components
import EmptyList from 'merchant/components/EmptyList';

const EmptyComponent = () => {
  return (
    <EmptyList description={<div>No payments found for the selected duration and criteria!</div>} />
  );
};

export default EmptyComponent;
