// red : 400,
// blue: 300
// green: 200
// grey: 500

export const RBL_STATUS = [
  {
    bankStatus: 'recieved',
    uiStatus: 'Telephonic verification',
    subText: 'RazorpayX executive will call you in a few days for  verification',
    code: 500,
    highlight: false,
  },
  {
    bankStatus: 'initiated',
    uiStatus: 'In-person verification & documents pick up',
    subText:
      'Our partner bank’s executive will help you fill the forms & collect them. Details will be shared soon.',
    code: 500,
    highlight: false,
  },
  {
    bankStatus: 'processing',
    uiStatus: 'Account activation',
    subText: 'We are working with our partner bank to get your account opened & activated',
    code: 500,
    highlight: false,
  },
];

export const RBL_ACTIVATE = [
  {
    bankStatus: 'activated',
    uiStatus: 'Start your Journey! ✨',
    subText: 'Your current account is now active, and you’re ready to take off!',
    code: 200,
    highlight: true,
  },
];

export const RBL_PAN_OR_APP = [
  {
    bankStatus: 'pending',
    uiStatus: 'Complete your application',
    subText:
      'Please complete your application to unleash the power of business banking & to reduce your present pricing to 1.65%',
    code: 300,
    highlight: true,
  },
  {
    bankStatus: 'panPending',
    uiStatus: 'PAN verification in progress',
    subText: 'We’re verifying your PAN details. Takes about 5-10 minutes to complete.',
    code: 300,
    highlight: true,
  },
  {
    bankStatus: 'panFailed',
    uiStatus: 'PAN verification failed',
    subText: 'Please enter a valid PAN number that is registered with your business to submit KYC.',
    code: 400,
    highlight: true,
  },
];

export const TRACKER_ERROR = [
  {
    bankStatus: 'cancelled',
    uiStatus: 'Request Cancelled',
    subText:
      'Your current account application has been cancelled. You can re-apply from the Banking section under My Account & Settings',
    code: 400,
    highlight: false,
  },
  {
    bankStatus: 'rejected',
    uiStatus: 'Request Rejected',
    subText:
      'Your current account application has been rejected by our banking partner. We will not be able to provide a Current Account at the moment but you can use the RazorpayX Basic Account.',
    code: 400,
    highlight: false,
  },
  {
    bankStatus: 'archived',
    uiStatus: 'Archived',
    subText:
      'We have archived your application to open Current Account with RazorpayX. Please reach out to our support team to restart the application. ',
    code: 400,
    highlight: false,
  },
  {
    bankStatus: 'unserviceable',
    uiStatus: 'Location unserviceable',
    subText:
      'Unfortunately, our banking partner can’t servoce at your loaction currently. However, you can use RazorpayX via a Basic account.',
    code: 400,
    highlight: false,
  },
];

export const derivedCaApplicationStatus = {
  PAN_PENDING: 'panPending',
  PAN_FAILED: 'panFailed',
  PENDING: 'pending',
  RECIEVED: 'recieved',
  INITIATED: 'initiated',
  PROCESSING: 'processing',
  PROCESSED: 'processed',
  CANCELLED: 'cancelled',
  UNSERVICEABLE: 'unserviceable',
  REJECTED: 'rejected',
  ARCHIVED: 'archived',
};

export const caApplicationStatus = {
  CREATED: 'created',
  PICKED: 'picked',
  INITIATED: 'initiated',
  PROCESSING: 'processing',
  PROCESSED: 'processed',
  CANCELLED: 'cancelled',
  ACTIVATED: 'activated',
  UNSERVICEABLE: 'unserviceable',
  REJECTED: 'rejected',
  ARCHIVED: 'archived',
};

export const caApplicationBlockedStatus = [
  caApplicationStatus.UNSERVICEABLE,
  caApplicationStatus.CANCELLED,
  caApplicationStatus.REJECTED,
  caApplicationStatus.ARCHIVED,
];
export const possiblePanValidationStatus = {
  incorrectDetails: 'incorrect_details',
  notMatched: 'not_matched',
  failed: 'failed',
  verified: 'verified',
  initiated: 'initiated',
  pending: 'pending',
};

export const panValidationFailureStatus = [
  possiblePanValidationStatus.incorrectDetails,
  possiblePanValidationStatus.notMatched,
  possiblePanValidationStatus.failed,
];

export const bankNamesMap = {
  RBL: 'RBL',
  ICICI: 'ICICI',
};
