import React from 'react';
import Form from 'common/new-ui/Form';

export const ChooseAccount = ({ values, setValues }) => {
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
                    name="account_type"
                    value="yes"
                    checked={values.has_account}
                    onChange={() => setValues({ ...values, has_account: true })}
                  />
                  I have a Registered Paytm Business Account
                </label>
              </div>
              <div className="form-control-radio">
                <label>
                  <input
                    type="radio"
                    name="account_type"
                    value="no"
                    defaultChecked={false}
                    checked={!values.has_account}
                    onChange={() => setValues({ ...values, has_account: false })}
                  />
                  I do not have a Registered Paytm Business Account
                </label>
              </div>
            </div>
          </div>
        </main>
      </Form>
      {!values.has_account && (
        <div className="register-account">
          <div className="blue-bar" />
          <p>
            Register for a Paytm Business Account on{' '}
            <a href="https://dashboard.paytm.com" rel="noreferrer noopener">
              dashboard.paytm.com
            </a>{' '}
            and try later
          </p>
        </div>
      )}
    </>
  );
};
