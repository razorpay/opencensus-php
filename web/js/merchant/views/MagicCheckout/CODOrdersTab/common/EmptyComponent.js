import EmptyList from 'merchant/components/EmptyList';

const EmptyComponent = () => {
  return (
    <EmptyList description={<div>No orders found for the selected duration and criteria!</div>} />
  );
};

export default EmptyComponent;
