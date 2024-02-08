import { V_KYC_STATUS } from 'merchant/reducers/videoKYCBanner';
import TimelineView from 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/components/UnlockMoreMethods/TimelineView';
import {
  KYC_DOCUMENT_STATUS_BADGE_MAPPING,
  UNLOCK_METHODS_STEPS,
  V_KYC_REJECTION,
  V_KYC_REJECTION_REASON_MAPPING,
  V_KYC_STATUS_BADGE_MAPPING,
} from 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/components/UnlockMoreMethods/constants';
import { ICProductStates } from 'merchant/views/AccountAndSettings/PaymentMethods/typings';
import { render, screen } from 'test-utils';

const renderComponent = (props) => {
  return render(<TimelineView {...props} />);
};

describe('Tests for TimelineView - UnlockMoreMethods component', () => {
  test('Should render without breaking', () => {
    expect(renderComponent()).toBeDefined();
  });

  test('Should show step title and description for each step', () => {
    renderComponent();

    UNLOCK_METHODS_STEPS.forEach((step) => {
      expect(screen.getByText(step.title)).toBeInTheDocument();
      //should show default description in case of status is undefined
      expect(screen.getByText(step.description)).toBeInTheDocument();
    });
  });

  test.each([
    ICProductStates.UNDER_REVIEW,
    ICProductStates.ACTION_REQUIRED,
    ICProductStates.ACTIVE,
  ])('Should show badge for KYC if status is other than rejected', (kycDocumentStatus) => {
    renderComponent({ kycDocumentStatus });

    const status = KYC_DOCUMENT_STATUS_BADGE_MAPPING[kycDocumentStatus];
    expect(screen.getByText(status.label)).toBeInTheDocument();

    //kyc description should change based on the status
    expect(screen.getByText(status.description)).toBeInTheDocument();
  });

  test('Should show button if kyc status is rejected', () => {
    const kycDocumentStatus = ICProductStates.REJECTED;
    renderComponent({ kycDocumentStatus });

    expect(screen.getByText(UNLOCK_METHODS_STEPS[0].ctaText.default)).toBeInTheDocument();
  });

  test.each([V_KYC_STATUS.APPROVED, V_KYC_STATUS.UNDER_REVIEW])(
    'Should show badge for VKYC if status is other than rejected and initiated',
    (vKycStatus) => {
      renderComponent({ vKycStatus });

      const status = V_KYC_STATUS_BADGE_MAPPING[vKycStatus];
      expect(screen.getByText(status.label)).toBeInTheDocument();

      //kyc description should change based on the status
      expect(screen.getByText(status.description)).toBeInTheDocument();
    },
  );

  test.each([
    { vKycStatus: V_KYC_STATUS.REJECTED, vKycRejectedReason: V_KYC_REJECTION.FACE_NOT_VISIBLE },
    { vKycStatus: V_KYC_STATUS.INITIATED },
  ])(
    'Should show button and description for VKYC if status is rejected or initiated',
    ({ vKycStatus, vKycRejectedReason }) => {
      renderComponent({ vKycStatus, vKycRejectedReason });

      const status = V_KYC_STATUS_BADGE_MAPPING[vKycStatus];
      expect(screen.getByText(UNLOCK_METHODS_STEPS[1].ctaText[vKycStatus])).toBeInTheDocument();

      if (vKycStatus === V_KYC_STATUS.REJECTED) {
        expect(
          screen.getByText(V_KYC_REJECTION_REASON_MAPPING[vKycRejectedReason], { exact: false }),
        ).toBeInTheDocument();
      } else {
        expect(screen.getByText(status.description));
      }
    },
  );
});
