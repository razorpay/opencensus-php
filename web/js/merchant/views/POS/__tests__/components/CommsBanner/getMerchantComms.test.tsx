import { getMerchantComms } from 'merchant/views/POS/CommsBanner/getMerchantComms';
import {
  MOCK_USER,
  MOCK_ORDER_RESPONSE,
  MOCK_DELIVERED_ORDER_ITEM,
} from 'merchant/views/POS/__tests__/mocks/fixtures';
import { OrderDetailsItem, RefundStatusTypes } from 'merchant/views/POS/types';
import { render, screen } from 'test-utils';

export const PROCESSED_REFUND = {
  id: 'mock-refund-id',
  amount: 12401,
  payment_id: 'mock-payment-id',
  created_at: 1200,
  status: 'processed' as RefundStatusTypes,
  acquirer_data: {
    RBL: 'ref-id-mock',
  },
};

export const PROCESSING_REFUND = {
  id: 'mock-refund-id',
  amount: 12401,
  payment_id: 'mock-payment-id',
  created_at: 1200,
  status: 'processing' as RefundStatusTypes,
  acquirer_data: {
    RBL: 'ref-id-mock',
  },
};

describe('getMerchantComms', () => {
  test('should return expected scenarios (0) if mode is test and KYC is null', () => {
    const user = {
      ...MOCK_USER,
      pos_activation_status: null,
    };
    const stages = getMerchantComms({
      user,
      mode: 'test',
      isMobileOrTablet: false,
    });

    expect(stages[0].status).toBe('active');
    expect(stages[0].title).toBe('Test Mode Enabled');
    expect(stages[0].description).toBe(
      'Explore POS from our detailed catalog and payments products available in test mode.',
    );
    expect(stages[0].cta?.[0].name).toBe('Explore POS');
    expect(stages[0].cta?.[0].url).toBe('/pos/catalog?focusProduct=true');
    expect(stages[0].cta?.[0].type).toBe('button');

    expect(stages[1].status).toBe('informationRequired');
    expect(stages[1].title).toBe('Account Activated');
    expect(stages[1].description).toBe(
      'Submit your KYC details to activate your account and to order POS.',
    );
    expect(stages[1].cta?.[0].name).toBe('Submit KYC');
    expect(stages[1].cta?.[0].url).toBe('https://easy.razorpay.com/onboarding');
    expect(stages[1].cta?.[0].type).toBe('button');

    expect(stages[2].status).toBe('pending');
    expect(stages[2].title).toBe('Access POS');
    expect(stages[2].description).toBe(
      'Explore out wide range of POS devices. Fill the KYC & shop details to place an order.',
    );
    expect(stages[2].cta).toStrictEqual([]);
  });

  test('should return expected scenarios (1) if offline kyc is UR and online not in NC and device ordered', () => {
    const user = {
      ...MOCK_USER,
      pos_activation_status: 'under_review',
      activation_status: 'under_review',
    };
    const stages = getMerchantComms({
      user,
      mode: 'live',
      isMobileOrTablet: false,
      order: MOCK_ORDER_RESPONSE.data,
    });
    expect(stages[0].status).toBe('active');
    expect(stages[0].title).toBe('KYC & POS Details Submitted');
    expect(stages[0].description).toBe(
      `Thank you for submitting your KYC & POS details. While we review your details, explore our wide range of POS devices and place your order.`,
    );
    expect(stages[0].cta).toStrictEqual([]);

    expect(stages[1].status).toBe('active');
    expect(stages[1].title).toBe('POS Order Placed');
    expect(stages[1].description).toBe(
      `Thank you for placing your POS order with Razorpay. Sit tight while we review your details and get your device delivered to you.`,
    );
    expect(stages[1].cta).toStrictEqual([]);
  });

  test('should return expected scenarios (2) if offline kyc is UR and online not in NC and device not ordered', () => {
    const user = {
      ...MOCK_USER,
      pos_activation_status: 'under_review',
      activation_status: 'under_review',
    };
    const stages = getMerchantComms({
      user,
      mode: 'live',
      isMobileOrTablet: false,
    });
    expect(stages[0].status).toBe('active');
    expect(stages[0].title).toBe('KYC & POS Details Submitted');
    expect(stages[0].cta).toStrictEqual([]);

    expect(stages[1].status).toBe('pending');
    expect(stages[1].title).toBe('Access POS');
  });

  test('should return expected scenarios (3) if offline kyc is UR and online not in NC and device ordered', () => {
    const user = {
      ...MOCK_USER,
      pos_activation_status: 'needs_clarification',
      activation_status: 'needs_clarification',
    };
    const stages = getMerchantComms({
      user,
      mode: 'live',
      isMobileOrTablet: false,
      order: MOCK_ORDER_RESPONSE.data,
    });
    expect(stages[0].status).toBe('notice');
    expect(stages[0].title).toBe('Update KYC');
    expect(stages[0].description).toBe(
      'Please note that you must update your required details to ensure timely delivery of your device.',
    );
    expect(stages[0].cta?.[0].name).toBe('Update KYC');
    expect(stages[0].cta?.[0].url).toBe('https://easy.razorpay.com/onboarding');
    expect(stages[0].cta?.[0].type).toBe('button');

    expect(stages[1].status).toBe('notice');
    expect(stages[1].title).toBe('POS Order Placed');
    expect(stages[1].description).toBe(
      'Your POS order is placed. But there is some problem with the KYC details you submitted. Please update your details .',
    );
    expect(stages[1].cta).toStrictEqual([]);

    expect(stages[2].status).toBe('dispatched');
    expect(stages[2].title).toBe('POS Dispatched');
    expect(stages[2].cta?.[0].name).toBe('Learn More');
    expect(stages[2].cta?.[0].url).toBe('/pos/orders/mock-order-id');
    expect(stages[2].cta?.[0].type).toBe('link');
  });

  test('should return expected scenarios (4) if offline kyc is UR and online not in NC and device not ordered', () => {
    const user = {
      ...MOCK_USER,
      pos_activation_status: 'needs_clarification',
      activation_status: 'needs_clarification',
    };
    const stages = getMerchantComms({
      user,
      mode: 'live',
      isMobileOrTablet: false,
    });
    expect(stages[0].status).toBe('notice');
    expect(stages[0].title).toBe('Update KYC');
    expect(stages[0].description).toBe(
      'To continue your POS journey, please ensure that you update the required details and then proceed to order your device.',
    );
    expect(stages[0].cta?.[0].url).toBe('https://easy.razorpay.com/onboarding');

    expect(stages[1].title).toBe('Access POS');
    expect(stages[1].description).toBe(
      `Explore out wide range of POS devices. Update your details to oder POS.`,
    );
    expect(stages[1].cta?.[0].name).toBe('Explore POS');
  });

  test('should return expected scenarios (5) if offline kyc qualified and device ordered', () => {
    const user = {
      ...MOCK_USER,
      pos_activation_status: 'kyc_qualified_stb',
    };
    const stages = getMerchantComms({
      user,
      mode: 'live',
      isMobileOrTablet: false,
      order: MOCK_ORDER_RESPONSE.data,
    });
    expect(stages[0].status).toBe('active');
    expect(stages[0].title).toBe('KYC Qualified');
    expect(stages[0].description).toBe(
      'Your POS details look good to us and is under final checks. Your device will be delivered to you in 7-9 business days.',
    );

    expect(stages[1].title).toBe('POS Order Placed');
    expect(stages[1].description).toBe(
      `Thank you for placing your POS order with Razorpay. Sit tight while we review your details and get your device delivered to you.`,
    );

    expect(stages[2].title).toBe('POS Dispatched');
    expect(stages[2].cta?.[0].name).toBe('Learn More');
  });

  test('should return expected scenarios (6) if offline kyc qualified and device not ordered', () => {
    const user = {
      ...MOCK_USER,
      pos_activation_status: 'kyc_qualified_stb',
    };
    const stages = getMerchantComms({
      user,
      mode: 'live',
      isMobileOrTablet: false,
    });
    expect(stages[0].status).toBe('active');
    expect(stages[0].title).toBe('KYC Qualified');
    expect(stages[0].description).toBe(
      'Good news! Your KYC has successfully cleared the initial checks. To expedite the process, please place your order now.',
    );

    expect(stages[1].title).toBe('Access POS');
    expect(stages[1].description).toBe(
      'Explore out wide range of POS devices. Order POS and we’ll bring your device in just 7-9 days.',
    );
    expect(stages[1].cta?.[0].name).toBe('Order POS');
  });

  test('should return expected scenarios (7) if offline activated and device ordered', () => {
    const user = {
      ...MOCK_USER,
      pos_activation_status: 'activated',
      activation_status: 'under_review',
    };
    const stages = getMerchantComms({
      user,
      mode: 'live',
      isMobileOrTablet: false,
      order: MOCK_ORDER_RESPONSE.data,
    });
    expect(stages[0].title).toBe('Account Activated');
    expect(stages[0].description).toBe(
      'Fantastic news! Your POS application is fully approved and finalised. Your order will reach you in just 2-3 business days.',
    );
    expect(stages[1].title).toBe('POS Order Placed');
    expect(stages[1].description).toBe(
      'Thank you for placing your POS order with Razorpay. We are now preparing your order.',
    );
  });

  test('should return expected scenarios (8) if offline rejected and online activated and device ordered', () => {
    const user = {
      ...MOCK_USER,
      pos_activation_status: 'rejected',
      activation_status: 'activated',
    };
    const stages = getMerchantComms({
      user,
      mode: 'live',
      isMobileOrTablet: false,
      order: MOCK_ORDER_RESPONSE.data,
    });

    expect(stages[0].title).toBe('Account Activated');
    expect(stages[0].description).toBe(
      'Congratulations! Your KYC is approved. Now you can access online payments.',
    );
    expect(stages[0].cta?.[0].name).toBe('Explore Online Payments');
    expect(stages[0].cta?.[0].url).toBe('/app/dashboard');

    expect(stages[1].title).toBe('POS Application Rejected');
    expect(stages[1].description).toBe(
      'Due to some issues with your POS KYC, we are rejecting your application for POS devices. And we will be cancelling your order.',
    );
    expect(stages[2].title).toBe('Refund Pending');
    render(stages[2].description);
    expect(screen.getByText(/We will initiate the refund of /)).toBeVisible();
    expect(screen.getByText('12,401')).toBeVisible();
  });

  test('should return expected scenarios (9) if offline rejected and online activated and device not ordered', () => {
    const user = {
      ...MOCK_USER,
      pos_activation_status: 'rejected',
      activation_status: 'activated',
    };
    const stages = getMerchantComms({
      user,
      mode: 'live',
      isMobileOrTablet: false,
    });

    expect(stages[0].title).toBe('Account Activated');
    expect(stages[0].description).toBe(
      'Congratulations! Your KYC is approved. Now you can access online payments.',
    );
    expect(stages[0].cta?.[0].name).toBe('Explore Online Payments');
    expect(stages[0].cta?.[0].url).toBe('/app/dashboard');

    expect(stages[1].title).toBe('POS Application Rejected');
  });

  test('should return expected scenarios (10) if offline rejected and online rejected and device ordered', () => {
    const user = {
      ...MOCK_USER,
      pos_activation_status: 'rejected',
      activation_status: 'rejected',
    };
    const stages = getMerchantComms({
      user,
      mode: 'live',
      isMobileOrTablet: false,
      order: MOCK_ORDER_RESPONSE.data,
    });

    expect(stages[0].title).toBe('KYC Rejected!');
    expect(stages[0].description).toBe(
      'We regret to inform you that your KYC has not been approved. For any questions, please contact our support team.',
    );
    expect(stages[1].title).toBe('POS Order Placed');
    expect(stages[1].description).toBe(
      'Since your KYC has been rejected, we won’t be able to dispatch your order. Your money will be refunded to your account within 5-7 business days.',
    );
    expect(stages[2].title).toBe('Refund Pending');
    render(stages[2].description);
    expect(screen.getByText(/We will initiate the refund of /)).toBeVisible();
    expect(screen.getByText('12,401')).toBeVisible();
  });

  test('should return expected scenarios (11) if offline rejected and online rejected and device ordered and refund initiated', () => {
    const user = {
      ...MOCK_USER,
      pos_activation_status: 'rejected',
      activation_status: 'rejected',
    };
    const stages = getMerchantComms({
      user,
      mode: 'live',
      isMobileOrTablet: false,
      order: {
        ...MOCK_ORDER_RESPONSE.data,
        refund: PROCESSING_REFUND,
      } as OrderDetailsItem,
    });

    expect(stages[0].title).toBe('KYC Rejected!');
    expect(stages[1].title).toBe('POS Order Placed');
    expect(stages[2].title).toBe('Refund Initiated');
    render(stages[2].description);
    expect(screen.getByText(/We have initiated the refund of/)).toBeVisible();
    expect(screen.getByText('12,401')).toBeVisible();
    expect(
      screen.getByText(/to original payment method. It will reflect in 5-7 business days/),
    ).toBeVisible();
  });

  test('should return expected scenarios (12) if offline rejected and online rejected and device ordered and refund completed', () => {
    const user = {
      ...MOCK_USER,
      pos_activation_status: 'rejected',
      activation_status: 'rejected',
    };
    const stages = getMerchantComms({
      user,
      mode: 'live',
      isMobileOrTablet: false,
      order: {
        ...MOCK_ORDER_RESPONSE.data,
        refund: PROCESSED_REFUND,
      } as OrderDetailsItem,
    });

    expect(stages[0].title).toBe('KYC Rejected!');
    expect(stages[1].title).toBe('POS Order Placed');
    expect(stages[2].title).toBe('Amount Refunded');
    render(stages[2].description);
    expect(screen.getByText(/We have refunded the amount of/)).toBeVisible();
    expect(screen.getByText('12,401')).toBeVisible();
    expect(
      screen.getByText(/to original payment method. It will reflect in 5-7 business days/),
    ).toBeVisible();
  });

  test('should return expected scenarios (13) if offline rejected and online rejected and device not ordered', () => {
    const user = {
      ...MOCK_USER,
      pos_activation_status: 'rejected',
      activation_status: 'rejected',
    };
    const stages = getMerchantComms({
      user,
      mode: 'live',
      isMobileOrTablet: false,
    });

    expect(stages[0].title).toBe('KYC Rejected!');
    expect(stages[0].description).toBe(
      'We regret to inform you that your KYC has not been approved. For any questions, please contact our support team.',
    );
    expect(stages[1].title).toBe('POS Access Denied!');
    expect(stages[1].description).toBe(
      'Since your KYC has been rejected, you won’t be able to order POS.',
    );
  });

  test('should return expected scenarios (14) if has online presence and offline is null and device not ordered', () => {
    const user = {
      ...MOCK_USER,
      business_website: 'www.dummy-website.com',
      appstore_url: 'www.dummy-website.com',
      playstore_url: 'www.dummy-website.com',
    };
    const stages = getMerchantComms({
      user,
      mode: 'live',
      isMobileOrTablet: false,
    });

    expect(stages[0].title).toBe('POS Application Under Review');
    expect(stages[0].description).toBe(
      'Your POS application is under review. We won’t be able to process your application until you order a device.',
    );
    expect(stages[0].cta?.[0].name).toBe('Order POS');
    expect(stages[0].cta?.[0].url).toBe('/pos/catalog?focusProduct=true');

    expect(stages[1].title).toBe('Access POS');
    expect(stages[1].description).toBe(
      'Explore our wide range of POS devices. Order POS and we’ll bring your device in just 2-3 business days post activation.',
    );
  });

  test('should return expected scenarios (15) if has no online presence and offline is null and device not ordered', () => {
    const stages = getMerchantComms({
      user: MOCK_USER,
      mode: 'live',
      isMobileOrTablet: false,
    });

    expect(stages[0].title).toBe('POS Details Required');
    expect(stages[0].description).toBe(
      'Please add few extra details to get activated with Razorpay POS.',
    );
    expect(stages[0].cta?.[0].name).toBe('Add Details');
    expect(stages[0].cta?.[0].url).toBe('https://easy.razorpay.com/onboarding');
    expect(stages[1].title).toBe('Access POS');
    expect(stages[1].description).toBe(
      'Explore out wide range of POS devices. Fill the shop details to place an order.',
    );
    expect(stages[1].cta?.[0].name).toBe('Add Details');
    expect(stages[1].cta?.[0].url).toBe('https://easy.razorpay.com/onboarding');
    expect(stages[1].cta?.[1].name).toBe('Explore POS');
    expect(stages[1].cta?.[1].url).toBe('/pos/catalog?focusProduct=true');
  });

  test('should return expected scenarios (16) if has offline is rejected and online is under review and device ordered ', () => {
    const user = {
      ...MOCK_USER,
      pos_activation_status: 'rejected',
      activation_status: 'under_review',
    };
    const stages = getMerchantComms({
      user,
      mode: 'live',
      isMobileOrTablet: false,
      order: MOCK_ORDER_RESPONSE.data,
    });

    expect(stages[0].title).toBe('Online KYC Under Review');
    expect(stages[0].description).toBe(
      'Your online application is under review. We will reach out to you in 3-4 business days in case of any queries.',
    );
    expect(stages[1].title).toBe('POS Application Rejected');
    expect(stages[2].title).toBe('Refund Pending');
  });

  test('should return expected scenarios (17) if has offline is rejected and online is kyc qualified but unactivated and device ordered ', () => {
    const user = {
      ...MOCK_USER,
      pos_activation_status: 'rejected',
      activation_status: 'kyc_qualified_unactivated',
    };
    const stages = getMerchantComms({
      user,
      mode: 'live',
      isMobileOrTablet: false,
      order: MOCK_ORDER_RESPONSE.data,
    });

    expect(stages[0].title).toBe('Online KYC Under Review');
    expect(stages[0].description).toBe(
      'Your online application is under review. Your account will be activated once the online onboarding pause is lifted.',
    );
    expect(stages[1].title).toBe('POS Application Rejected');
    expect(stages[2].title).toBe('Refund Pending');
  });

  test('should return expected scenarios (19) if has offline is rejected and online is under review and device ordered ', () => {
    const user = {
      ...MOCK_USER,
      pos_activation_status: 'rejected',
      activation_status: 'under_review',
    };
    const stages = getMerchantComms({
      user,
      mode: 'live',
      isMobileOrTablet: false,
    });

    expect(stages[0].title).toBe('Online KYC Under Review');
    expect(stages[0].description).toBe(
      'Your online application is under review. We will reach out to you in 3-4 business days in case of any queries.',
    );
    expect(stages[1].title).toBe('POS Application Rejected');
  });

  test('should return expected scenarios (18) if has offline is rejected and online is kyc qualified but unactivated and device not ordered ', () => {
    const user = {
      ...MOCK_USER,
      pos_activation_status: 'rejected',
      activation_status: 'kyc_qualified_unactivated',
    };
    const stages = getMerchantComms({
      user,
      mode: 'live',
      isMobileOrTablet: false,
    });

    expect(stages[0].title).toBe('Online KYC Under Review');
    expect(stages[0].description).toBe(
      'Your online application is under review. Your account will be activated once the online onboarding pause is lifted.',
    );
    expect(stages[1].title).toBe('POS Application Rejected');
  });

  test('should return null if order is delivered', () => {
    const stages = getMerchantComms({
      user: {
        ...MOCK_USER,
        pos_activation_status: 'under_review',
      },
      mode: 'test',
      isMobileOrTablet: false,
      order: MOCK_DELIVERED_ORDER_ITEM,
    });

    expect(stages).toStrictEqual([]);
  });
});
