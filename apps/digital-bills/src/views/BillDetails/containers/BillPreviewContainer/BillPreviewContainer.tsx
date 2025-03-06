import React from 'react';
import { Box, Card, CardBody, Text } from '@razorpay/blade/components';
import styled from 'styled-components';

import getBillUrl from '@apps/digital-bills/src/utils/helpers/getBillUrl';

const Iframe = styled.iframe`
  width: 100%;
  height: 100%;
  border: 0;
`;

type BillPreviewContainerProps = {
  id: string | null;
  signedToken: string | undefined;
};

const BillPreviewContainer = ({
  id,
  signedToken,
}: BillPreviewContainerProps): React.ReactElement => {
  return (
    <Card height="100%" backgroundColor="surface.background.gray.moderate">
      <CardBody height="100%">
        <Box display="flex" gap="spacing.4" flexDirection="column" height="100%">
          <Text weight="semibold">Bill Preview</Text>
          <Iframe id="billme-bill-preview" src={`${getBillUrl({ id, signedToken })}`} />
        </Box>
      </CardBody>
    </Card>
  );
};

export default BillPreviewContainer;
