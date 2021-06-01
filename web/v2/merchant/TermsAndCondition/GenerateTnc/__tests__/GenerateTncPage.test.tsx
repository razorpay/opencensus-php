/* eslint-disable @typescript-eslint/no-unused-expressions */
import React from 'react';
import { useQuery } from 'react-query';
import 'regenerator-runtime/runtime';
import '@testing-library/jest-dom/extend-expect';
import GenerateTncPage from '../index';
import { fireEvent, render, cleanup, waitFor } from 'test-utils';
import { renderHook } from '@testing-library/react-hooks';

afterEach(() => {
  cleanup();
});

function useCustomHook() {
  return useQuery('customHook', () => {
    const data = {
      link: 'tnc.razorpay.com/Gmfaslj9AUIOH',
      merchant_id: '4VUhFiV6029ARY',
      deliverable_type: 'goods',
      support_email: 'abc@gmail.com',
      shipping_period: '1-2 days',
      refund_request_period: '3-5 days',
      refund_process_period: '9-15 days',
      warranty_period: 'NA',
    };
    return data;
  });
}

describe('GenerateTncPage', () => {
  it('should enable the Generate Page button', async () => {
    const { getByRole } = render(<GenerateTncPage />, {});
    const consentCheckBox = getByRole('checkbox', { checked: false });
    await waitFor(() => {
      fireEvent.change(consentCheckBox, { target: { checked: true } });
    });
    expect(<GenerateTncPage />).toMatchSnapshot();
  });

  it('should input value get filled', async () => {
    const { getAllByTestId } = render(<GenerateTncPage />, {});

    const supportEmail = getAllByTestId('ds-text-input')[0];
    const shippingPeriod = getAllByTestId('ds-text-input')[1];
    const refundRequest = getAllByTestId('ds-text-input')[2];
    const refundProcess = getAllByTestId('ds-text-input')[3];
    const warrantyPeriod = getAllByTestId('ds-text-input')[4];

    await waitFor(() => {
      fireEvent.change(supportEmail, { target: { value: 'abc@gmail.com' } });
      fireEvent.change(shippingPeriod, { target: { value: '1-2 days' } });
      fireEvent.change(refundRequest, { target: { value: '3-5 days' } });
      fireEvent.change(refundProcess, { target: { value: '9-15 days' } });
      fireEvent.change(warrantyPeriod, { target: { value: 'NA' } });
    });

    expect(supportEmail).toMatchSnapshot();
    expect(shippingPeriod).toMatchSnapshot();
    expect(refundRequest).toMatchSnapshot();
    expect(refundProcess).toMatchSnapshot();
    expect(warrantyPeriod).toMatchSnapshot();
  });

  it('should get success respose on api call', async () => {
    const { getAllByTestId, getByRole } = render(<GenerateTncPage />, {});
    const button = getByRole('button', { name: /Generate Page/i });

    const supportEmail = getAllByTestId('ds-text-input')[0];
    const shippingPeriod = getAllByTestId('ds-text-input')[1];
    const refundRequest = getAllByTestId('ds-text-input')[2];
    const refundProcess = getAllByTestId('ds-text-input')[3];
    const warrantyPeriod = getAllByTestId('ds-text-input')[4];

    await waitFor(() => {
      fireEvent.change(supportEmail, { target: { value: 'abc@gmail.com' } });
      fireEvent.change(shippingPeriod, { target: { value: '1-2 days' } });
      fireEvent.change(refundRequest, { target: { value: '3-5 days' } });
      fireEvent.change(refundProcess, { target: { value: '9-15 days' } });
      fireEvent.change(warrantyPeriod, { target: { value: 'NA' } });
    });

    await waitFor(() => {
      fireEvent.click(button);
    });
    const { result, waitForNextUpdate } = renderHook(() => useCustomHook());

    result.current.isSuccess;
    await waitForNextUpdate();

    expect(result.current.data?.link).toEqual('tnc.razorpay.com/Gmfaslj9AUIOH');
    expect(result.current.data?.merchant_id).toEqual('4VUhFiV6029ARY');
    expect(result.current.data?.deliverable_type).toEqual('goods');
    expect(result.current.data?.refund_request_period).toEqual('3-5 days');
    expect(result.current.data?.shipping_period).toEqual('1-2 days');
    expect(result.current.data?.support_email).toEqual('abc@gmail.com');
    expect(result.current.data?.refund_process_period).toEqual('9-15 days');
    expect(result.current.data?.warranty_period).toEqual('NA');
  });
});
