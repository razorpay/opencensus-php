import { render, screen } from '@testing-library/react';
import moment from 'moment';

import SubMerchantKycStatusLabel from 'merchant/views/PartnerDashboard/SubMerchant/components/SubMerchantKycStatusLabel';
import '@testing-library/jest-dom/extend-expect';

describe('<SubMerchantKycStatusLabel />', () => {
  test("Partner didn't request SubM", () => {
    render(<SubMerchantKycStatusLabel />);
    expect(screen.getByText('Pending Completion')).toBeInTheDocument();
  });

  test('SubM  approval pending', () => {
    let notExpiredTime = new Date().getTime() + 100000;
    notExpiredTime = moment(notExpiredTime).unix();

    const props = {
      activation_status: null,
      kyc_access: {
        state: 'pending_approval',
        rejection_count: 0,
        token_expiry: notExpiredTime,
      },
    };
    render(<SubMerchantKycStatusLabel {...props} />);
    expect(screen.getByText('Pending Completion')).toBeInTheDocument();
    expect(screen.getByText('Waiting for Merchant approval')).toBeInTheDocument();
  });

  test('SubM didnt take action on request and request expired', () => {
    let expiredTime = new Date().getTime() - 100000;
    expiredTime = moment(expiredTime).unix();
    const props = {
      activation_status: null,
      kyc_access: {
        state: 'pending_approval',
        rejection_count: 1,
        token_expiry: expiredTime,
      },
    };
    render(<SubMerchantKycStatusLabel {...props} />);
    expect(screen.getByText('Pending Completion')).toBeInTheDocument();
    expect(screen.getByText('Request expired')).toBeInTheDocument();
  });

  test('SubM rejected request 1 time', () => {
    let notExpiredTime = new Date().getTime() + 100000;
    notExpiredTime = moment(notExpiredTime).unix();
    const props = {
      activation_status: null,
      kyc_access: {
        state: 'rejected',
        rejection_count: 1,
        token_expiry: notExpiredTime,
      },
    };
    render(<SubMerchantKycStatusLabel {...props} />);
    expect(screen.getByText('Pending Completion')).toBeInTheDocument();
    expect(screen.getByText('Rejected by merchant once')).toBeInTheDocument();
  });

  test('SubM rejected request 2 time', () => {
    let notExpiredTime = new Date().getTime() + 100000;
    notExpiredTime = moment(notExpiredTime).unix();
    const props = {
      activation_status: null,
      kyc_access: {
        state: 'rejected',
        rejection_count: 2,
        token_expiry: notExpiredTime,
      },
    };
    render(<SubMerchantKycStatusLabel {...props} />);
    expect(screen.getByText('Pending Completion')).toBeInTheDocument();
    expect(screen.getByText('Rejected by merchant twice')).toBeInTheDocument();
  });

  test('SubM rejected request 3 time', () => {
    let notExpiredTime = new Date().getTime() + 100000;
    notExpiredTime = moment(notExpiredTime).unix();
    const props = {
      activation_status: null,
      kyc_access: {
        state: 'rejected',
        rejection_count: 3,
        token_expiry: notExpiredTime,
      },
    };
    render(<SubMerchantKycStatusLabel {...props} />);
    expect(screen.getByText('Pending Completion')).toBeInTheDocument();
    expect(screen.getByText('Rejected by merchant thrice')).toBeInTheDocument();
  });

  test('SubM approved request', () => {
    let notExpiredTime = new Date().getTime() + 100000;
    notExpiredTime = moment(notExpiredTime).unix();
    const props = {
      activation_status: null,
      kyc_access: {
        state: 'approved',
        rejection_count: 1,
        token_expiry: notExpiredTime,
      },
    };
    render(<SubMerchantKycStatusLabel {...props} />);
    expect(screen.getByText('Pending Completion')).toBeInTheDocument();
    expect(screen.getByText('Request approved by Merchant')).toBeInTheDocument();
  });

  test('KYC request submitted to Razorpay', () => {
    let notExpiredTime = new Date().getTime() + 100000;
    notExpiredTime = moment(notExpiredTime).unix();
    const props = {
      activation_status: 'activated',
      kyc_access: {
        state: 'approved',
        rejection_count: 1,
        token_expiry: notExpiredTime,
      },
    };
    render(<SubMerchantKycStatusLabel {...props} />);

    expect(screen.getByText('Activated')).toBeInTheDocument();

    // checking if not exist
    expect(screen.queryByText('Pending Completion')).toBeNull();
    expect(screen.queryByText('Request approved by Merchant')).toBeNull();
  });
});
