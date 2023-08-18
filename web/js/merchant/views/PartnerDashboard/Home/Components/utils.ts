import confetti from 'canvas-confetti';
import { getItem, setItem } from 'common/utils/localStorage';
import { StepContentT } from 'merchant/views/PartnerDashboard/Home/TypesDeclare/home';

export const showActivationConfetti = (
  stepName: StepContentT['stepName'],
  isCompletedStep: boolean,
): void => {
  const desktopCanvas = document.getElementById('confettiActivationGuide') as HTMLCanvasElement;
  const mobCanvas = document.getElementById('confettiActivationGuideMob') as HTMLCanvasElement;
  let confettiCanvas = desktopCanvas;
  if (window.innerWidth < 1024) {
    confettiCanvas = mobCanvas;
  }
  const isConfettiShown = !!getItem(`fux-${stepName}`);
  if (isCompletedStep && !isConfettiShown) {
    const canvasConfetti = confetti.create(confettiCanvas, {
      resize: true,
      useWorker: true,
    });
    canvasConfetti({
      spread: 160,
      origin: { y: 1.2 },
    });
    setItem(`fux-${stepName}`, true);
  }
};

export const getExperimentsForTracking = (user: any) => {
  return {
    shorterPartnerKYC: user?.isIndependentPartnerKYCEnabled ? 'yes' : 'no',
    onboardAllAsReseller: user?.isOnboardAsResellers ? 'yes' : 'no',
  };
};
