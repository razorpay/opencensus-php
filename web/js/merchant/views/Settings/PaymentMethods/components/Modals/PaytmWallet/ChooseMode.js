import React from 'react';
import Form from 'common/new-ui/Form';
import { DetailsDrawer } from './DetailsDrawer';

export const ChooseMode = ({ values, setValues }) => {
  return (
    <>
      <Form>
        <main>
          <div className="form-container">
            <p className="form-title">Select a relevant option</p>
            <div className="form-description form-radio" id="white-background">
              <div className="form-control-radio">
                <label>
                  <input
                    type="radio"
                    name="mode_type"
                    checked={values.mode === 'live'}
                    onChange={() => {
                      setValues({ ...values, mode: 'live' });
                    }}
                  />
                  <div>
                    <p>Enter Production API Details</p>
                    <span>Customers will be able to transact with Paytm Wallet on Checkout</span>
                  </div>
                </label>
              </div>
              <div className="form-control-radio">
                <label>
                  <input
                    type="radio"
                    name="mode_type"
                    checked={values.mode === 'test'}
                    onChange={() => {
                      setValues({ ...values, mode: 'test' });
                    }}
                  />
                  <div>
                    <p>Enter Test API Details</p>
                    <span>
                      Wallet will be enabled on Test Mode. You can only do test transactions with
                      Paytm Wallet
                    </span>
                  </div>
                </label>
              </div>
            </div>
          </div>
        </main>
      </Form>
      <DetailsDrawer />
    </>
  );
};
