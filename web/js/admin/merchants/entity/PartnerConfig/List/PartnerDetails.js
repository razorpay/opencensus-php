import { Link } from 'react-router-dom';

import EntityRow from 'ui/EntityRow';

export default function PartnerDetails(props) {
  const { partner } = props;
  return (
    <>
      <div class="heading">Partner Details</div>
      <EntityRow
        label="Partner Id"
        value={() => (
          <Link to={`/merchants/${partner.id}`} class="link">
            {partner.id}
          </Link>
        )}
      />
      <EntityRow label="Partner Name" value={partner.name} />
    </>
  );
}
