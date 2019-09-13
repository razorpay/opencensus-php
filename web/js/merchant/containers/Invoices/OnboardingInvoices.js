import EmptyList from 'merchant/components/EmptyList';

export default () => (
  <EmptyList
    description={
      <React.Fragment>
        <div>There are no invoices yet!!</div>
        <div>Start creating new invoices now.</div>
      </React.Fragment>
    }
  />
);
