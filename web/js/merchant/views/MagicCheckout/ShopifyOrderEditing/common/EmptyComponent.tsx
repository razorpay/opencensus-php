import React from 'react';
import EmptyList from 'merchant/components/EmptyList';

const EmptyComponent: React.FC = () => {
  return <EmptyList description={<div>No orders found for the criteria!</div>} />;
};

export default EmptyComponent;
