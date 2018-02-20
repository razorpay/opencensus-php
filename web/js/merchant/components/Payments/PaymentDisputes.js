import React from 'react';

import Amount from 'rzp/ui/Amount';
import ContentToggler from 'rzp/ui/Toggler/ContentToggler';
import Definition from 'rzp/ui/Definition';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
import { dispute as Id } from 'rzp/ui/item/id';
import { titleCase } from 'rzp/utils/rzp-utils';
import { DisputeStatusLabel as StatusLabel } from 'merchant/components/StatusLabel';

export default ({ disputes }) => {
  return (
    <ContentToggler>
      <span>{disputes.length} Disputes raised</span>
      {disputes.map(dispute => (
        <Definition allowEmptyTitle={true} key={dispute.id} customClass="m-t">
          {Id(dispute)}
          <div>
            {titleCase(dispute.phase)}, <Amount value={dispute.amount} />
          </div>
          <StatusLabel status={dispute.status} />
        </Definition>
      ))}
    </ContentToggler>
  );
};
