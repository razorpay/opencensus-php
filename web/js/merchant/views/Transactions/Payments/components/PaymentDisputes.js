import React from 'react';

import Amount from 'common/ui/Amount';
import ContentToggler from 'common/ui/Toggler/ContentToggler';
import Definition from 'common/ui/Definition';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
import { dispute as Id } from 'common/ui/item/id';
import { titleCase } from 'common/utils/rzp-utils';
import { DisputeStatusLabel as StatusLabel } from 'merchant/components/StatusLabel';

export default ({ disputes }) => {
  return (
    <ContentToggler>
      <span>{disputes.length} Disputes raised</span>
      {disputes.map(dispute => (
        <Definition allowEmptyTitle={true} key={dispute.id} customClass="m-t">
          {Id(dispute)}
          <div>
            {titleCase(dispute.phase)},{' '}
            <Amount value={dispute.amount} currency={dispute.currency} />
          </div>
          <StatusLabel status={dispute.status} />
        </Definition>
      ))}
    </ContentToggler>
  );
};
