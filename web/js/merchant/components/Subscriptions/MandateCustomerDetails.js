import Definition from 'rzp/ui/Definition';

export default function MandateCustomerDetails({ customer }) {
  return (
    <Definition>
      <strong>{customer.name}</strong>
      <>{customer.email}</>
      <>{customer.contact}</>
      <> {customer.id}</>
    </Definition>
  );
}
