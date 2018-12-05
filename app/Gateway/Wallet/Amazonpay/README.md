# Amazon Pay Wallet

**Documentation**:
https://drive.google.com/open?id=1eYrSunmXQ3JFSQ5qm3pHYvaAxsq3LENT

**People**: Junaid / Vivek

# Payment flow
- Buyer places order, and that hits `Razorpay` Server.
- `Razorpay` adds a signature and encrypts the request using the `AmazonPay` SDK code.
- `Razorpay` makes a request to `processPayment` on `AmazonPay` server.
- `AmazonPay` makes request to selected payment method server and gets the response.
- Signed response is sent from `AmazonPay` back to `Razorpay`.
- `Razorpay` must verify the signature sent in the response.
- Thank the buyer.

# Important points
- Return URL needs to be whitelisted by `AmazonPay` for Live.
- The SDK contains encryption logic. We will have to build the decryption logic ourselves for mock server.
- For success, and failed cases, `AmazonPay` returns the user to the return url with the corresponding parameters.
- The response sent back will contain a signature, which must be verified. An example of the response is outlined below:
    ```
      {
        "amazonOrderId": "S04-3441699-5326071",
        "description": "Txn Success",
        "orderTotalAmount": "1.00",
        "orderTotalCurrencyCode": "INR",
        "reasonCode": "001",
        "sellerOrderId": "9deD4YbHVIuUMY",
        "signature": "U3Kgb18hTRDO+6PkWwHzpBjI9ypeyLWQD5E9nnrye4M=",
        "status": "SUCCESS",
        "transactionDate": "1519025049876"
      }
    ```
# Encryption Logic
- `AmazonPay` uses `AES-GCM` No Padding algorithm for plaintext encryption.
- The plaintext is encrypted with a key and the key is then encrypted using `RSA-ECB` with `OAEPWithSHA-1AndMGF1Padding` algorithm. This concept of encrypting the key that can used to decrypt the `ciphertext` is called envelope encryption. The key encryption key `(KEK)` is used to encrypt the data key.
- `Razorpay` sends as part of the request, 3 parameters to `AmazonPay`
    ```
      1. The encrypted payload
      2. The encrypted data key to decrypt the above payload
      3. The initialization vector used to decrypt the above payload
    ```
# Verify flow
- `AmazonPay` calls this API `ListOrderReference` - to check the details of a previously placed order
- The verify API requests are throttled for sandbox and production environments
- `Razorpay` creates the request array as per the API docs
- `Razorpay` makes a call to the `listOrderReference` method in the SDK
- The `listOrderReference` method returns the request url which `Razorpay` uses to make a server to server call
- The response is an xml, as per the API docs in the link above
- `Razorpay` parses the xml and checks for the status of the verification call

# Refund flow
- The `AmazonPay` Refund API supports both partial and full refunds.
- If the refund is successful, it is in `Completed` state and if it fails, it is in `Declined` state.
- The Refund API has the following features
    ```
      1. Make a call to the RefundPayment API.
      2. Listen for the Refund IPN returned by AmazonPay.
      3. Refunds are not processed in real time, and the initial status is always pending.
      4. When the refund is processed, the returned state is completed or declined.
      5. We can get the details of the refund via the GetRefundStatus API using the AmazonRefundId returned in the Refund API response.
    ```
- Refund API
    ```
      1. Rate limited and throttled API
      2. Generate the Refund API request params
      3. Make a call to the SDK's refund API
      4. The returned response will be that of a refund in pending state
      5. AmazonPay processes the refund after some time and notifies Razorpay, sometimes after several hours via an IPN message
      6. Razorpay gets the pending response, and notes that the refund was added to AmazonPay's request queue, and changes the internal refund status to INITIATED
      7. To get the status of the refund (processed, failed) from AmazonPay, we must go via the IPN flow, or the Verify Refund API
    ```

- Verify Refund API
    - `Razorpay` will get the status of the refund via this API and not via the IPN system
    ```
      1. If a refund's request to AmazonPay failed, then the refund would have been updated to failed. If the response was received, we update the status to initiated
      2. Via Verify Refund we will pick up all the refunds in failed / initiated state and send AmazonPay a verify refund API call
      3. Via the response, we will be notified of the refund's status, and we will update the internal refund status accordingly
    ```
