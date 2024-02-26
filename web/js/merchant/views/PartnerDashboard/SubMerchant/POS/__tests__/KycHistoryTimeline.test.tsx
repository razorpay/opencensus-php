import React from 'react';

import { render, screen } from 'common/services/test/test-utils';
import { KycHistoryTimeline } from 'merchant/views/PartnerDashboard/SubMerchant/POS/Components/KycHistoryTimeline';
import {
  parsedActionStatesType,
  parsedClarificationReasonsType,
} from 'merchant/views/PartnerDashboard/SubMerchant/POS/TypeDeclares';
import {
  parseActionStates,
  parseClarificationReasons,
} from 'merchant/views/PartnerDashboard/SubMerchant/POS/utils';

import {
  actionStateResponse,
  posSubmerchantDetailsResponse,
  actionStateResponseWithRejected,
} from './mocks/fixtures';

const {
  data: { details },
} = posSubmerchantDetailsResponse;
describe('KycHistoryTimeline', () => {
  const renderApp = (
    actionStateResponse: parsedActionStatesType | undefined,
    clarificationReasons: parsedClarificationReasonsType | undefined,
  ) => {
    return render(
      <KycHistoryTimeline
        actionState={actionStateResponse}
        clarificationReasons={clarificationReasons}
      />,
    );
  };
  test('should render the timeline', () => {
    renderApp(
      parseActionStates(actionStateResponse),
      parseClarificationReasons(details.kyc_clarification_reasons.clarification_reasons_v2),
    );
    expect(screen.getByText('Submitted')).toBeInTheDocument();
    expect(screen.getAllByText('Needs Clarification')).toHaveLength(2);
    expect(screen.getByText('aadhar_front: illegible_doc')).toBeInTheDocument();
    expect(screen.getByText('aadhar_front: test_case')).toBeInTheDocument();
    expect(screen.getByText('Under Review')).toBeInTheDocument();
    expect(screen.getByText('Activated')).toBeInTheDocument();
  });

  test('should render rejected status', () => {
    renderApp(
      parseActionStates(actionStateResponseWithRejected),
      parseClarificationReasons(details.kyc_clarification_reasons.clarification_reasons_v2),
    );
    expect(screen.getByText('Submitted')).toBeInTheDocument();
    expect(screen.getByText('Rejected')).toBeInTheDocument();
  });

  test('should render N/A if no data is present', () => {
    renderApp(undefined, undefined);
    expect(screen.getByText('N/A')).toBeInTheDocument();
  });
});
