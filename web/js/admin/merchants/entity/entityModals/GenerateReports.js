import React from 'react';

import { ModalContent } from 'component/Modal';

import GenerateReports from 'admin/reports';

export default props => (
  <ModalContent header="Download Reports" class="reports-modal" noPadding>
    <GenerateReports {...props} />
  </ModalContent>
);
